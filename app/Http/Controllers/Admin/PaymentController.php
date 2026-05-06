<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesRoleTableAccess;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    use AuthorizesRoleTableAccess;

    public function __construct(private readonly MidtransService $midtrans)
    {
    }

    public function index()
    {
        $this->ensureTablePermission('payments', 'read');
        $user = Auth::user();
        $search = request('q');
        $status = request('status');
        $method = request('method');

        $payments = Payment::with(['shipment.sender'])
            ->when($user->role === User::ROLE_COURIER, function ($query) use ($user) {
                $query->whereHas('shipment', function ($subQuery) use ($user) {
                    $subQuery->where('courier_id', $user->id);
                });
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('gateway_order_id', 'like', '%' . $search . '%')
                        ->orWhere('gateway_transaction_id', 'like', '%' . $search . '%')
                        ->orWhereHas('shipment', fn ($q) => $q->where('tracking_number', 'like', '%' . $search . '%'))
                        ->orWhereHas('shipment.sender', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->when($status, fn ($query) => $query->where('payment_status', $status))
            ->when($method, fn ($query) => $query->where('payment_method', $method))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summaryBaseQuery = Payment::query()
            ->when($user->role === User::ROLE_COURIER, function ($query) use ($user) {
                $query->whereHas('shipment', function ($subQuery) use ($user) {
                    $subQuery->where('courier_id', $user->id);
                });
            });

        $payments->getCollection()->transform(function (Payment $payment) {
            if (!$payment->isFinal() || $payment->payment_status === Payment::STATUS_PENDING) {
                return $this->midtrans->refreshPaymentStatus($payment);
            }

            return $payment;
        });

        return view('admin.payments.index', [
            'title' => 'Payments',
            'payments' => $payments,
            'summary' => [
                'total' => (clone $summaryBaseQuery)->count(),
                'pending' => (clone $summaryBaseQuery)->where('payment_status', Payment::STATUS_PENDING)->count(),
                'paid' => (clone $summaryBaseQuery)->where('payment_status', Payment::STATUS_PAID)->count(),
                'failed' => (clone $summaryBaseQuery)->where('payment_status', Payment::STATUS_FAILED)->count(),
                'revenue_paid' => (clone $summaryBaseQuery)->where('payment_status', Payment::STATUS_PAID)->sum('amount'),
            ],
            'filters' => [
                'q' => $search,
                'status' => $status,
                'method' => $method,
            ],
            'statuses' => Payment::statuses(),
            'methods' => Payment::methods(),
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('payments.index')->with('error', 'Pembayaran hanya bisa dibuat melalui Midtrans dari portal customer.');
    }

    public function store(): RedirectResponse
    {
        return redirect()->route('payments.index')->with('error', 'Pembayaran manual dinonaktifkan. Gunakan Midtrans.');
    }

    public function edit(Payment $payment): RedirectResponse
    {
        return redirect()->route('payments.index')->with('error', 'Pembayaran Midtrans tidak bisa diedit manual.');
    }

    public function update(Payment $payment): RedirectResponse
    {
        return redirect()->route('payments.index')->with('error', 'Pembayaran Midtrans tidak bisa diperbarui manual.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        return redirect()->route('payments.index')->with('error', 'Pembayaran Midtrans tidak boleh dihapus manual.');
    }
}
