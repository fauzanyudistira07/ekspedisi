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
        $staleShipmentThreshold = now()->subDays(2);
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
            'payments_waiting' => (clone $paymentsQuery)->where('payment_status', Payment::STATUS_PENDING)->count(),
            'payments_paid_today' => (clone $paymentsQuery)
                ->where('payment_status', Payment::STATUS_PAID)
                ->whereDate('paid_at', $today)
                ->sum('amount'),
            'payments_failed' => (clone $paymentsQuery)->where('payment_status', Payment::STATUS_FAILED)->count(),
        ];

        $alerts = collect();

        $staleShipments = (clone $shipmentsQuery)
            ->whereIn('status', [
                Shipment::STATUS_PENDING,
                Shipment::STATUS_PICKED_UP,
                Shipment::STATUS_IN_TRANSIT,
                Shipment::STATUS_ARRIVED_AT_BRANCH,
                Shipment::STATUS_OUT_FOR_DELIVERY,
            ])
            ->whereDate('shipment_date', '<=', $staleShipmentThreshold->toDateString())
            ->count();

        if ($staleShipments > 0) {
            $alerts->push([
                'tone' => 'warning',
                'title' => 'Shipment Butuh Perhatian',
                'description' => $staleShipments . ' shipment aktif belum selesai lebih dari 2 hari.',
                'route' => route('shipments.index', ['status' => Shipment::STATUS_IN_TRANSIT]),
                'action' => 'Review Shipment',
            ]);
        }

        $pendingMidtrans = (clone $paymentsQuery)
            ->where('payment_status', Payment::STATUS_PENDING)
            ->count();

        if ($pendingMidtrans > 0 && $currentRole !== User::ROLE_COURIER) {
            $alerts->push([
                'tone' => 'info',
                'title' => 'Pembayaran Midtrans Pending',
                'description' => $pendingMidtrans . ' transaksi masih menunggu penyelesaian dari customer/gateway.',
                'route' => route('payments.index', ['status' => Payment::STATUS_PENDING]),
                'action' => 'Lihat Pending',
            ]);
        }

        $failedPayments = (clone $paymentsQuery)
            ->where('payment_status', Payment::STATUS_FAILED)
            ->count();

        if ($failedPayments > 0 && $currentRole !== User::ROLE_COURIER) {
            $alerts->push([
                'tone' => 'danger',
                'title' => 'Pembayaran Gagal',
                'description' => $failedPayments . ' pembayaran gagal dan berpotensi menahan shipment.',
                'route' => route('payments.index', ['status' => Payment::STATUS_FAILED]),
                'action' => 'Audit Payment',
            ]);
        }

        $deliveredWithoutFreshTracking = (clone $shipmentsQuery)
            ->where('status', Shipment::STATUS_DELIVERED)
            ->whereDoesntHave('trackings', function ($query) {
                $query->where('status', ShipmentTracking::STATUS_DELIVERED)
                    ->whereNotNull('proof_photo');
            })
            ->count();

        if ($deliveredWithoutFreshTracking > 0) {
            $alerts->push([
                'tone' => 'warning',
                'title' => 'Delivery Proof Belum Lengkap',
                'description' => $deliveredWithoutFreshTracking . ' shipment delivered belum punya tracking delivered yang valid.',
                'route' => route('shipments.index', ['status' => Shipment::STATUS_DELIVERED]),
                'action' => 'Cek Shipment',
            ]);
        }

        $kpiCards = $currentRole === User::ROLE_COURIER
            ? [
                [
                    'label' => 'Task Saya',
                    'value' => number_format($stats['shipments_total']),
                    'meta' => number_format($stats['shipments_today']) . ' assignment hari ini',
                    'route' => route('courier.tasks'),
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Sedang Berjalan',
                    'value' => number_format($stats['shipments_in_transit']),
                    'meta' => number_format($stats['trackings_today']) . ' tracking diinput hari ini',
                    'route' => route('courier.tasks'),
                    'tone' => 'warning',
                ],
                [
                    'label' => 'Delivered',
                    'value' => number_format($stats['shipments_delivered']),
                    'meta' => 'Pastikan semua delivery punya POD',
                    'route' => route('shipment-trackings.index', ['status' => ShipmentTracking::STATUS_DELIVERED]),
                    'tone' => 'success',
                ],
                [
                    'label' => 'Tracking Hari Ini',
                    'value' => number_format($stats['trackings_today']),
                    'meta' => 'Update perjalanan paket Anda',
                    'route' => route('shipment-trackings.index'),
                    'tone' => 'info',
                ],
            ]
            : [
                [
                    'label' => 'Total Shipment',
                    'value' => number_format($stats['shipments_total']),
                    'meta' => number_format($stats['shipments_today']) . ' dibuat hari ini',
                    'route' => route('shipments.index'),
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Dalam Perjalanan',
                    'value' => number_format($stats['shipments_in_transit']),
                    'meta' => number_format($stats['trackings_today']) . ' update tracking hari ini',
                    'route' => route('shipments.index', ['status' => Shipment::STATUS_IN_TRANSIT]),
                    'tone' => 'warning',
                ],
                [
                    'label' => 'Pending Payment',
                    'value' => number_format($stats['payments_waiting']),
                    'meta' => 'Midtrans sandbox/production',
                    'route' => route('payments.index', ['status' => Payment::STATUS_PENDING]),
                    'tone' => 'info',
                ],
                [
                    'label' => 'Uang Masuk',
                    'value' => 'Rp ' . number_format((float) $stats['payments_paid_today'], 0, ',', '.'),
                    'meta' => number_format($stats['payments_failed']) . ' pembayaran gagal',
                    'route' => route('payments.index', ['status' => Payment::STATUS_PAID]),
                    'tone' => 'success',
                ],
            ];

        $quickActions = match ($currentRole) {
            User::ROLE_COURIER => [
                ['label' => 'Task Saya', 'route' => route('courier.tasks'), 'style' => 'btn-primary'],
                ['label' => 'Shipment Saya', 'route' => route('shipments.index'), 'style' => 'btn-outline-light'],
                ['label' => 'Riwayat Tracking', 'route' => route('shipment-trackings.index'), 'style' => 'btn-outline-light'],
            ],
            User::ROLE_CASHIER,
            User::ROLE_CASIER => [
                ['label' => 'Pending Midtrans', 'route' => route('payments.index', ['status' => Payment::STATUS_PENDING]), 'style' => 'btn-primary'],
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
            User::ROLE_CASHIER,
            User::ROLE_CASIER => 'Fokus hari ini: monitor transaksi Midtrans pending, pastikan status pembayaran tersinkron dengan benar.',
            User::ROLE_MANAGER => 'Fokus hari ini: awasi SLA pengiriman, progres tracking, dan status pembayaran yang bermasalah.',
            default => 'Fokus hari ini: pastikan master data, operasional shipment, dan pembayaran berjalan konsisten.',
        };

        return view('dashboard.index', [
            'title' => 'Dashboard',
            'currentRole' => $currentRole,
            'currentRoleFeatures' => $roleMatrix[$currentRole]['features'] ?? [],
            'roleFocus' => $roleFocus,
            'alerts' => $alerts,
            'kpiCards' => $kpiCards,
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
