<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Shipment;
use App\Models\ShipmentTracking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $currentRole = $user?->role;
        $roleMatrix = config('role_feature_matrix.roles');

        $shipmentsQuery = Shipment::with(['sender', 'receiver', 'courier']);
        $paymentsQuery = Payment::with('shipment');
        $trackingsQuery = ShipmentTracking::with('shipment');

        if ($currentRole === User::ROLE_COURIER) {
            $shipmentsQuery->where('courier_id', $user->id);
            $paymentsQuery->whereHas('shipment', fn ($query) => $query->where('courier_id', $user->id));
            $trackingsQuery->whereHas('shipment', fn ($query) => $query->where('courier_id', $user->id));
        }

        $today = now()->toDateString();
        $todayShipments = (clone $shipmentsQuery)->whereDate('shipment_date', $today)->count();
        $todayTrackings = (clone $trackingsQuery)->whereDate('tracked_at', $today)->count();
        $inTransitStatuses = [
            Shipment::STATUS_PICKED_UP,
            Shipment::STATUS_IN_TRANSIT,
            Shipment::STATUS_ARRIVED_AT_BRANCH,
            Shipment::STATUS_OUT_FOR_DELIVERY,
        ];

        $stats = [
            'shipments_total' => (clone $shipmentsQuery)->count(),
            'shipments_in_transit' => (clone $shipmentsQuery)->whereIn('status', $inTransitStatuses)->count(),
            'shipments_delivered' => (clone $shipmentsQuery)->where('status', Shipment::STATUS_DELIVERED)->count(),
            'shipments_today' => $todayShipments,
            'trackings_today' => $todayTrackings,
            'payments_waiting' => (clone $paymentsQuery)->whereIn('payment_status', [
                Payment::STATUS_PENDING,
                Payment::STATUS_WAITING_VERIFICATION,
            ])->count(),
            'payments_paid_today' => (clone $paymentsQuery)
                ->where('payment_status', Payment::STATUS_PAID)
                ->whereDate('payment_date', $today)
                ->sum('amount'),
            'payments_failed' => (clone $paymentsQuery)->where('payment_status', Payment::STATUS_FAILED)->count(),
        ];

        $quickActions = match ($currentRole) {
            User::ROLE_COURIER => [
                ['label' => 'Task Courier', 'route' => route('courier.tasks'), 'style' => 'btn-primary'],
                ['label' => 'Update Tracking', 'route' => route('shipment-trackings.create'), 'style' => 'btn-outline-light'],
                ['label' => 'Daftar Kendaraan', 'route' => route('vehicles.index'), 'style' => 'btn-outline-light'],
            ],
            User::ROLE_CASHIER => [
                ['label' => 'Verifikasi Pembayaran', 'route' => route('payments.verification'), 'style' => 'btn-primary'],
                ['label' => 'Input Pembayaran Baru', 'route' => route('payments.create'), 'style' => 'btn-outline-light'],
                ['label' => 'Riwayat Pembayaran', 'route' => route('payments.index'), 'style' => 'btn-outline-light'],
            ],
            User::ROLE_MANAGER => [
                ['label' => 'Laporan Manager', 'route' => route('manager.reports'), 'style' => 'btn-primary'],
                ['label' => 'Monitor Shipment', 'route' => route('shipments.index'), 'style' => 'btn-primary'],
                ['label' => 'Monitor Payment', 'route' => route('payments.index'), 'style' => 'btn-outline-light'],
                ['label' => 'Task Courier', 'route' => route('courier.tasks'), 'style' => 'btn-outline-light'],
            ],
            default => [
                ['label' => 'Kelola User', 'route' => route('users.index'), 'style' => 'btn-primary'],
                ['label' => 'Kelola Shipment', 'route' => route('shipments.index'), 'style' => 'btn-outline-light'],
                ['label' => 'Kelola Payment', 'route' => route('payments.index'), 'style' => 'btn-outline-light'],
            ],
        };

        $roleFocus = match ($currentRole) {
            User::ROLE_COURIER => 'Fokus hari ini: update tracking tepat waktu, pastikan semua paket transit mendapat status terbaru.',
            User::ROLE_CASHIER => 'Fokus hari ini: verifikasi bukti pembayaran transfer/e-wallet dan selesaikan antrian payment.',
            User::ROLE_MANAGER => 'Fokus hari ini: awasi SLA pengiriman, progres tracking, dan status pembayaran yang bermasalah.',
            default => 'Fokus hari ini: pastikan master data, operasional shipment, dan pembayaran berjalan konsisten.',
        };

        return view('dashboard.index', [
            'title' => 'Dashboard',
            'currentRole' => $currentRole,
            'currentRoleFeatures' => $roleMatrix[$currentRole]['features'] ?? [],
            'roleFocus' => $roleFocus,
            'quickActions' => $quickActions,
            'stats' => $stats,
            'recentShipments' => (clone $shipmentsQuery)->latest()->limit(5)->get(),
            'recentPayments' => (clone $paymentsQuery)->latest()->limit(5)->get(),
            'recentTrackings' => (clone $trackingsQuery)->latest('tracked_at')->limit(5)->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
