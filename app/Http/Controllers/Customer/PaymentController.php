<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Shipment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
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
            'title' => 'Create Payment',
            'shipments' => $shipments,
            'methods' => Payment::methods(),
        ]);
    }

    public function store(Request $request)
    {
        $customerId = Auth::guard('customer')->id();

        $validated = $request->validate([
            'shipment_id' => [
                'required',
                'exists:shipments,id',
                Rule::exists('shipments', 'id')->where(fn ($query) => $query->where('sender_id', $customerId)),
            ],
            'payment_method' => ['required', Rule::in(Payment::methods())],
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string|max:120',
            'proof_file' => 'nullable|image|mimes:jpg,jpeg,png|max:3072',
            'notes' => 'nullable|string|max:1000',
        ]);

        $shipment = Shipment::where('sender_id', $customerId)->findOrFail($validated['shipment_id']);

        $latestPayment = Payment::where('shipment_id', $shipment->id)->latest()->first();

        if ($latestPayment && !in_array($latestPayment->payment_status, [
            Payment::STATUS_FAILED,
            Payment::STATUS_EXPIRED,
            Payment::STATUS_REFUNDED,
        ], true)) {
            return back()->withErrors(['shipment_id' => 'Shipment ini sudah memiliki pembayaran.'])->withInput();
        }

        $paymentStatus = $validated['payment_method'] === Payment::METHOD_CASH
            ? Payment::STATUS_PENDING
            : Payment::STATUS_WAITING_VERIFICATION;

        $proofFile = null;
        if ($request->hasFile('proof_file')) {
            $proofFile = time() . '_proof_' . $request->file('proof_file')->getClientOriginalName();
            $request->file('proof_file')->move(public_path('uploads/payments'), $proofFile);
        }

        if ($validated['payment_method'] !== Payment::METHOD_CASH && empty($proofFile)) {
            return back()->withErrors(['proof_file' => 'Bukti pembayaran wajib diunggah untuk transfer/e-wallet.'])->withInput();
        }

        $payload = [
            'shipment_id' => $shipment->id,
            'amount' => $shipment->total_price,
            'payment_method' => $validated['payment_method'],
            'reference_number' => $validated['reference_number'] ?? null,
            'proof_file' => $proofFile,
            'payment_status' => $paymentStatus,
            'payment_date' => $validated['payment_date'],
            'expired_at' => now()->addDay(),
            'verified_at' => null,
            'verified_by' => null,
            'notes' => $validated['notes'] ?? null,
        ];

        if ($latestPayment && in_array($latestPayment->payment_status, [
            Payment::STATUS_FAILED,
            Payment::STATUS_EXPIRED,
            Payment::STATUS_REFUNDED,
        ], true)) {
            $latestPayment->update($payload);
        } else {
            Payment::create($payload);
        }

        return redirect()->route('customer.payments.index')->with('success', 'Payment berhasil dibuat. Silakan pantau status verifikasi pada menu payment.');
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
}
