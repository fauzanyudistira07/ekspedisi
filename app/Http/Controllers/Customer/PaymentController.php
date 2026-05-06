<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Shipment;
use App\Services\MidtransService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function __construct(private readonly MidtransService $midtrans)
    {
    }

    public function index()
    {
        $customerId = Auth::guard('customer')->id();
        $status = request('status');
        $search = request('q');

        $payments = Payment::with('shipment')
            ->whereHas('shipment', function ($query) use ($customerId) {
                $query->where('sender_id', $customerId);
            })
            ->when($status, fn ($query) => $query->where('payment_status', $status))
            ->when($search, function ($query) use ($search) {
                $query->whereHas('shipment', function ($subQuery) use ($search) {
                    $subQuery->where('tracking_number', 'like', '%' . $search . '%');
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $payments->getCollection()->transform(function (Payment $payment) {
            if (!$payment->isFinal() || $payment->payment_status === Payment::STATUS_PENDING) {
                return $this->midtrans->refreshPaymentStatus($payment);
            }

            return $payment;
        });

        return view('customer.payments.index', [
            'title' => 'My Payments',
            'payments' => $payments,
            'filters' => [
                'status' => $status,
                'q' => $search,
            ],
            'statuses' => Payment::statuses(),
        ]);
    }

    public function create()
    {
        $customerId = Auth::guard('customer')->id();

        Payment::with('shipment')
            ->where('payment_status', Payment::STATUS_PENDING)
            ->whereHas('shipment', fn ($query) => $query->where('sender_id', $customerId))
            ->get()
            ->each(fn (Payment $payment) => $this->midtrans->refreshPaymentStatus($payment));

        $shipments = Shipment::with('payment')
            ->where('sender_id', $customerId)
            ->where(function ($query) {
                $query->whereDoesntHave('payment')
                    ->orWhereHas('payment', function ($paymentQuery) {
                        $paymentQuery->whereIn('payment_status', [
                            Payment::STATUS_FAILED,
                            Payment::STATUS_EXPIRED,
                            Payment::STATUS_REFUNDED,
                        ]);
                    });
            })
            ->orderByDesc('id')
            ->get();

        return view('customer.payments.create', [
            'title' => 'Bayar Dengan Midtrans',
            'shipments' => $shipments,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customerId = Auth::guard('customer')->id();

        $validated = $request->validate([
            'shipment_id' => [
                'required',
                'exists:shipments,id',
                Rule::exists('shipments', 'id')->where(fn ($query) => $query->where('sender_id', $customerId)),
            ],
        ]);

        $shipment = Shipment::with(['sender', 'receiver'])->where('sender_id', $customerId)->findOrFail($validated['shipment_id']);
        $latestPayment = Payment::where('shipment_id', $shipment->id)->latest()->first();

        if ($latestPayment && $latestPayment->payment_status === Payment::STATUS_PENDING) {
            $latestPayment = $this->midtrans->refreshPaymentStatus($latestPayment);
        }

        if ($latestPayment && !in_array($latestPayment->payment_status, [
            Payment::STATUS_FAILED,
            Payment::STATUS_EXPIRED,
            Payment::STATUS_REFUNDED,
        ], true)) {
            if (empty($latestPayment->snap_token) || empty($latestPayment->snap_redirect_url)) {
                $this->prepareSnapTransaction($latestPayment, $shipment);
            }

            return redirect()->route('customer.payments.checkout', $latestPayment);
        }

        $payment = $latestPayment ?? new Payment();
        $payment->fill([
            'shipment_id' => $shipment->id,
            'amount' => $shipment->total_price,
            'gateway_provider' => 'midtrans',
            'gateway_order_id' => $this->midtrans->generateGatewayOrderId($shipment),
            'payment_method' => Payment::METHOD_MIDTRANS,
            'payment_channel' => null,
            'reference_number' => null,
            'proof_file' => null,
            'payment_status' => Payment::STATUS_PENDING,
            'midtrans_transaction_status' => 'pending',
            'payment_date' => null,
            'paid_at' => null,
            'verified_at' => null,
            'expired_at' => now()->addDay(),
            'verified_by' => null,
            'notes' => 'Checkout via Midtrans',
            'gateway_payload' => null,
        ]);
        $payment->save();

        $this->prepareSnapTransaction($payment, $shipment);

        return redirect()->route('customer.payments.checkout', $payment);
    }

    public function checkout(Payment $customerPayment)
    {
        $payment = $this->ensurePaymentOwnership($customerPayment);
        $payment = $this->midtrans->refreshPaymentStatus($payment);

        if ($payment->payment_status === Payment::STATUS_PAID) {
            return redirect()->route('customer.payments.index')
                ->with('success', 'Pembayaran sudah berhasil dikonfirmasi oleh Midtrans.');
        }

        if (empty($payment->snap_token) || empty($payment->snap_redirect_url)) {
            $this->prepareSnapTransaction($payment, $payment->shipment);
            $payment->refresh();
        }

        return view('customer.payments.checkout', [
            'title' => 'Checkout Midtrans',
            'payment' => $payment->load('shipment'),
            'midtransClientKey' => config('services.midtrans.client_key'),
            'midtransSnapUrl' => config('services.midtrans.is_production')
                ? 'https://app.midtrans.com/snap/snap.js'
                : 'https://app.sandbox.midtrans.com/snap/snap.js',
        ]);
    }

    public function result(Payment $customerPayment): RedirectResponse
    {
        $payment = $this->ensurePaymentOwnership($customerPayment);
        $payment = $this->midtrans->refreshPaymentStatus($payment);

        $message = match ($payment->payment_status) {
            Payment::STATUS_PAID => 'Pembayaran berhasil dikonfirmasi.',
            Payment::STATUS_PENDING => 'Pembayaran masih menunggu penyelesaian di Midtrans.',
            Payment::STATUS_EXPIRED => 'Transaksi Midtrans sudah kedaluwarsa. Silakan buat pembayaran baru.',
            Payment::STATUS_FAILED => 'Pembayaran gagal atau dibatalkan.',
            default => 'Status pembayaran telah diperbarui.',
        };

        return redirect()->route('customer.payments.index')->with('success', $message);
    }

    public function notification(): JsonResponse
    {
        $payment = $this->midtrans->handleNotification();

        return response()->json([
            'message' => 'Notification processed',
            'payment_id' => $payment->id,
            'status' => $payment->payment_status,
        ]);
    }

    public function invoice(Payment $customerPayment): Response
    {
        $customerId = Auth::guard('customer')->id();

        $payment = Payment::with(['shipment.sender', 'shipment.receiver', 'shipment.originBranch', 'shipment.destinationBranch', 'shipment.items'])
            ->findOrFail($customerPayment->id);

        if ($payment->shipment->sender_id !== $customerId) {
            abort(403, 'Anda tidak memiliki akses invoice ini.');
        }

        $pdf = Pdf::loadView('customer.payments.invoice', [
            'payment' => $payment,
            'shipment' => $payment->shipment,
            'customer' => Auth::guard('customer')->user(),
        ])->setPaper('a4');

        return $pdf->download('invoice-' . $payment->shipment->tracking_number . '.pdf');
    }

    private function ensurePaymentOwnership(Payment $payment): Payment
    {
        $customerId = Auth::guard('customer')->id();
        $payment->loadMissing('shipment');

        if ($payment->shipment->sender_id !== $customerId) {
            abort(403, 'Anda tidak memiliki akses ke pembayaran ini.');
        }

        return $payment;
    }

    private function prepareSnapTransaction(Payment $payment, Shipment $shipment): void
    {
        $payment->loadMissing(['shipment.sender', 'shipment.receiver']);

        if (empty($payment->gateway_order_id)) {
            $payment->update([
                'gateway_order_id' => $this->midtrans->generateGatewayOrderId($shipment),
            ]);
            $payment->refresh();
        }

        $snap = $this->midtrans->createOrRefreshSnapTransaction($payment);

        $payment->update([
            'snap_token' => $snap['token'],
            'snap_redirect_url' => $snap['redirect_url'],
            'gateway_payload' => $snap['payload'],
        ]);
    }
}
