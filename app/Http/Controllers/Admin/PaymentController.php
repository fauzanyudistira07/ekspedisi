<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesRoleTableAccess;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    use AuthorizesRoleTableAccess;

    public function index()
    {
        $this->ensureTablePermission('payments', 'read');
        $this->markExpiredPayments();
        $user = Auth::user();
        $search = request('q');
        $status = request('status');
        $method = request('method');

        $payments = Payment::with('shipment')
            ->when($user->role === User::ROLE_COURIER, function ($query) use ($user) {
                $query->whereHas('shipment', function ($subQuery) use ($user) {
                    $subQuery->where('courier_id', $user->id);
                });
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('reference_number', 'like', '%' . $search . '%')
                        ->orWhereHas('shipment', fn ($q) => $q->where('tracking_number', 'like', '%' . $search . '%'));
                });
            })
            ->when($status, fn ($query) => $query->where('payment_status', $status))
            ->when($method, fn ($query) => $query->where('payment_method', $method))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.payments.index', [
            'title' => 'Payments',
            'payments' => $payments,
            'filters' => [
                'q' => $search,
                'status' => $status,
                'method' => $method,
            ],
            'statuses' => Payment::statuses(),
            'methods' => Payment::methods(),
        ]);
    }

    public function create()
    {
        $this->ensureTablePermission('payments', 'create');

        return view('admin.payments.create', [
            'title' => 'Create Payment',
            'shipments' => $this->visibleShipments(),
            'methods' => Payment::methods(),
            'statuses' => Payment::statuses(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureTablePermission('payments', 'create');

        $validated = $request->validate([
            'shipment_id' => 'required|exists:shipments,id|unique:payments,shipment_id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => ['required', Rule::in(Payment::methods())],
            'payment_status' => ['required', Rule::in(Payment::statuses())],
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string|max:120',
            'proof_file' => 'nullable|image|mimes:jpg,jpeg,png|max:3072',
            'notes' => 'nullable|string|max:1000',
        ]);

        $shipment = Shipment::findOrFail($validated['shipment_id']);
        $this->ensureShipmentVisibility($shipment);

        $proofFile = null;
        if ($request->hasFile('proof_file')) {
            $proofFile = time() . '_proof_' . $request->file('proof_file')->getClientOriginalName();
            $request->file('proof_file')->move(public_path('uploads/payments'), $proofFile);
        }

        if ($validated['payment_method'] !== Payment::METHOD_CASH && empty($proofFile)) {
            return back()
                ->withErrors(['proof_file' => 'Bukti pembayaran wajib diunggah untuk transfer/e-wallet.'])
                ->withInput();
        }

        $validated['proof_file'] = $proofFile;

        if ($validated['payment_status'] === Payment::STATUS_PAID) {
            $validated['verified_at'] = now();
            $validated['verified_by'] = Auth::id();
        }

        Payment::create($validated);

        return redirect()->route('payments.index')->with('success', 'Payment berhasil ditambahkan.');
    }

    public function edit(Payment $payment)
    {
        $this->ensureTablePermission('payments', 'update');
        $this->ensureShipmentVisibility($payment->shipment);

        return view('admin.payments.edit', [
            'title' => 'Edit Payment',
            'payment' => $payment,
            'shipments' => $this->visibleShipments(),
            'methods' => Payment::methods(),
            'statuses' => Payment::statuses(),
        ]);
    }

    public function update(Request $request, Payment $payment)
    {
        $this->ensureTablePermission('payments', 'update');
        $this->ensureShipmentVisibility($payment->shipment);

        $validated = $request->validate([
            'shipment_id' => 'required|exists:shipments,id|unique:payments,shipment_id,' . $payment->id,
            'amount' => 'required|numeric|min:0',
            'payment_method' => ['required', Rule::in(Payment::methods())],
            'payment_status' => ['required', Rule::in(Payment::statuses())],
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string|max:120',
            'proof_file' => 'nullable|image|mimes:jpg,jpeg,png|max:3072',
            'notes' => 'nullable|string|max:1000',
        ]);

        $shipment = Shipment::findOrFail($validated['shipment_id']);
        $this->ensureShipmentVisibility($shipment);

        $proofFile = $payment->proof_file;
        if ($request->hasFile('proof_file')) {
            $proofFile = time() . '_proof_' . $request->file('proof_file')->getClientOriginalName();
            $request->file('proof_file')->move(public_path('uploads/payments'), $proofFile);
        }

        if ($validated['payment_method'] !== Payment::METHOD_CASH && empty($proofFile)) {
            return back()
                ->withErrors(['proof_file' => 'Bukti pembayaran wajib diunggah untuk transfer/e-wallet.'])
                ->withInput();
        }

        $validated['proof_file'] = $proofFile;

        if ($validated['payment_status'] === Payment::STATUS_PAID) {
            $validated['verified_at'] = $payment->verified_at ?? now();
            $validated['verified_by'] = $payment->verified_by ?? Auth::id();
        } else {
            $validated['verified_at'] = null;
            $validated['verified_by'] = null;
        }

        $payment->update($validated);

        return redirect()->route('payments.index')->with('success', 'Payment berhasil diperbarui.');
    }

    public function destroy(Payment $payment)
    {
        $this->ensureTablePermission('payments', 'delete');
        $this->ensureShipmentVisibility($payment->shipment);

        $payment->delete();

        return redirect()->route('payments.index')->with('success', 'Payment berhasil dihapus.');
    }

    public function verificationQueue()
    {
        $this->ensureTablePermission('payments', 'read');
        $this->ensureVerificationDashboardAccess();
        $this->markExpiredPayments();
        $search = request('q');
        $method = request('method');

        $payments = Payment::with(['shipment.sender', 'shipment.receiver'])
            ->whereIn('payment_status', [
                Payment::STATUS_PENDING,
                Payment::STATUS_WAITING_VERIFICATION,
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('reference_number', 'like', '%' . $search . '%')
                        ->orWhereHas('shipment', fn ($q) => $q->where('tracking_number', 'like', '%' . $search . '%'))
                        ->orWhereHas('shipment.sender', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->when($method, fn ($query) => $query->where('payment_method', $method))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.payments.verification', [
            'title' => 'Payment Verification',
            'payments' => $payments,
            'filters' => [
                'q' => $search,
                'method' => $method,
            ],
            'methods' => Payment::methods(),
        ]);
    }

    public function verify(Request $request, Payment $payment)
    {
        $this->ensureTablePermission('payments', 'update');
        $this->ensureVerificationActionAccess();
        $this->ensureCanBeVerified($payment);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $payment->update([
            'payment_status' => Payment::STATUS_PAID,
            'payment_date' => $payment->payment_date ?? now()->toDateString(),
            'verified_at' => now(),
            'verified_by' => Auth::id(),
            'notes' => $validated['notes'] ?? $payment->notes,
        ]);

        return back()->with('success', 'Pembayaran berhasil diverifikasi.');
    }

    public function reject(Request $request, Payment $payment)
    {
        $this->ensureTablePermission('payments', 'update');
        $this->ensureVerificationActionAccess();
        $this->ensureCanBeVerified($payment);

        $validated = $request->validate([
            'notes' => 'required|string|max:1000',
        ], [
            'notes.required' => 'Catatan penolakan wajib diisi.',
        ]);

        $payment->update([
            'payment_status' => Payment::STATUS_FAILED,
            'verified_at' => null,
            'verified_by' => null,
            'notes' => $validated['notes'],
        ]);

        return back()->with('success', 'Pembayaran ditolak dan status diubah menjadi failed.');
    }

    private function visibleShipments()
    {
        $user = Auth::user();

        return Shipment::when($user->role === User::ROLE_COURIER, function ($query) use ($user) {
            $query->where('courier_id', $user->id);
        })->orderBy('tracking_number')->get();
    }

    private function ensureShipmentVisibility(Shipment $shipment): void
    {
        $user = Auth::user();

        if ($user && $user->role === User::ROLE_COURIER && $shipment->courier_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke shipment ini.');
        }
    }

    private function ensureVerificationDashboardAccess(): void
    {
        $user = Auth::user();

        if (!$user || !$user->hasAnyRole([
            User::ROLE_ADMIN,
            User::ROLE_CASHIER,
            User::ROLE_MANAGER,
        ])) {
            abort(403, 'Anda tidak memiliki akses ke dashboard verifikasi pembayaran.');
        }
    }

    private function ensureVerificationActionAccess(): void
    {
        $user = Auth::user();

        if (!$user || !$user->hasAnyRole([
            User::ROLE_ADMIN,
            User::ROLE_CASHIER,
        ])) {
            abort(403, 'Hanya admin/cashier yang boleh memverifikasi pembayaran.');
        }
    }

    private function markExpiredPayments(): void
    {
        Payment::whereIn('payment_status', [
            Payment::STATUS_PENDING,
            Payment::STATUS_WAITING_VERIFICATION,
        ])
            ->whereNotNull('expired_at')
            ->where('expired_at', '<=', now())
            ->update([
                'payment_status' => Payment::STATUS_EXPIRED,
            ]);
    }

    private function ensureCanBeVerified(Payment $payment): void
    {
        if (!in_array($payment->payment_status, [
            Payment::STATUS_PENDING,
            Payment::STATUS_WAITING_VERIFICATION,
        ], true)) {
            abort(422, 'Status pembayaran ini sudah final dan tidak bisa diverifikasi ulang.');
        }
    }
}
