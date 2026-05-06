<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\ShipmentTracking;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ManagerReportController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureAccess();

        [$dateFrom, $dateTo] = $this->resolveDateRange(
            $request->input('date_from'),
            $request->input('date_to')
        );
        $dateFromString = $dateFrom->toDateString();
        $dateToString = $dateTo->toDateString();
        $branchId = $request->input('branch_id');

        $shipmentQuery = Shipment::query()
            ->whereDate('shipment_date', '>=', $dateFromString)
            ->whereDate('shipment_date', '<=', $dateToString)
            ->when($branchId, function ($query) use ($branchId) {
                $query->where(function ($subQuery) use ($branchId) {
                    $subQuery->where('origin_branch_id', $branchId)
                        ->orWhere('destination_branch_id', $branchId);
                });
            });

        $paymentQuery = Payment::query()
            ->whereDate('created_at', '>=', $dateFromString)
            ->whereDate('created_at', '<=', $dateToString)
            ->when($branchId, function ($query) use ($branchId) {
                $query->whereHas('shipment', function ($subQuery) use ($branchId) {
                    $subQuery->where(function ($innerQuery) use ($branchId) {
                        $innerQuery->where('origin_branch_id', $branchId)
                            ->orWhere('destination_branch_id', $branchId);
                    });
                });
            });

        $statusCounts = (clone $shipmentQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $staleThreshold = now()->subDays(2)->toDateString();
        $staleShipmentCount = (clone $shipmentQuery)
            ->whereIn('status', [
                Shipment::STATUS_PENDING,
                Shipment::STATUS_PICKED_UP,
                Shipment::STATUS_IN_TRANSIT,
                Shipment::STATUS_ARRIVED_AT_BRANCH,
                Shipment::STATUS_OUT_FOR_DELIVERY,
                Shipment::STATUS_EXCEPTION_HOLD,
                Shipment::STATUS_FAILED_DELIVERY,
            ])
            ->whereDate('shipment_date', '<=', $staleThreshold)
            ->count();

        $summary = [
            'total_shipments' => (clone $shipmentQuery)->count(),
            'delivered' => (int) ($statusCounts[Shipment::STATUS_DELIVERED] ?? 0),
            'in_transit' => (int) (($statusCounts[Shipment::STATUS_PICKED_UP] ?? 0)
                + ($statusCounts[Shipment::STATUS_IN_TRANSIT] ?? 0)
                + ($statusCounts[Shipment::STATUS_ARRIVED_AT_BRANCH] ?? 0)
                + ($statusCounts[Shipment::STATUS_OUT_FOR_DELIVERY] ?? 0)),
            'cancelled' => (int) ($statusCounts[Shipment::STATUS_CANCELLED] ?? 0),
            'exceptions' => (int) (($statusCounts[Shipment::STATUS_FAILED_DELIVERY] ?? 0)
                + ($statusCounts[Shipment::STATUS_EXCEPTION_HOLD] ?? 0)
                + ($statusCounts[Shipment::STATUS_RETURNED_TO_SENDER] ?? 0)),
            'paid_amount' => (float) (clone $paymentQuery)->where('payment_status', Payment::STATUS_PAID)->sum('amount'),
            'waiting_payment' => (int) (clone $paymentQuery)->where('payment_status', Payment::STATUS_PENDING)->count(),
            'failed_payment' => (int) (clone $paymentQuery)->where('payment_status', Payment::STATUS_FAILED)->count(),
            'stale_shipments' => $staleShipmentCount,
        ];

        $exceptionBreakdown = [
            [
                'label' => 'Gagal Antar',
                'count' => (int) ($statusCounts[Shipment::STATUS_FAILED_DELIVERY] ?? 0),
                'status' => Shipment::STATUS_FAILED_DELIVERY,
            ],
            [
                'label' => 'Hold / Exception',
                'count' => (int) ($statusCounts[Shipment::STATUS_EXCEPTION_HOLD] ?? 0),
                'status' => Shipment::STATUS_EXCEPTION_HOLD,
            ],
            [
                'label' => 'Retur ke Pengirim',
                'count' => (int) ($statusCounts[Shipment::STATUS_RETURNED_TO_SENDER] ?? 0),
                'status' => Shipment::STATUS_RETURNED_TO_SENDER,
            ],
        ];

        $slaAlerts = collect();

        if ($staleShipmentCount > 0) {
            $slaAlerts->push([
                'tone' => 'warning',
                'title' => 'Shipment Melewati Batas Pantau',
                'description' => $staleShipmentCount . ' shipment aktif berumur lebih dari 2 hari.',
            ]);
        }

        if ($summary['exceptions'] > 0) {
            $slaAlerts->push([
                'tone' => 'danger',
                'title' => 'Shipment Bermasalah',
                'description' => $summary['exceptions'] . ' shipment sedang berada di status exception operasional.',
            ]);
        }

        if ($summary['waiting_payment'] > 0) {
            $slaAlerts->push([
                'tone' => 'info',
                'title' => 'Pembayaran Menunggu',
                'description' => $summary['waiting_payment'] . ' pembayaran masih pending dan bisa menahan pengiriman tertentu.',
            ]);
        }

        $staleShipments = (clone $shipmentQuery)
            ->with(['originBranch', 'destinationBranch', 'courier'])
            ->whereIn('status', [
                Shipment::STATUS_PENDING,
                Shipment::STATUS_PICKED_UP,
                Shipment::STATUS_IN_TRANSIT,
                Shipment::STATUS_ARRIVED_AT_BRANCH,
                Shipment::STATUS_OUT_FOR_DELIVERY,
                Shipment::STATUS_EXCEPTION_HOLD,
                Shipment::STATUS_FAILED_DELIVERY,
            ])
            ->whereDate('shipment_date', '<=', $staleThreshold)
            ->orderBy('shipment_date')
            ->limit(8)
            ->get();

        $branchPerformance = Branch::query()
            ->select('branches.id', 'branches.name', 'branches.city')
            ->when($branchId, fn ($query) => $query->where('branches.id', $branchId))
            ->withCount([
                'originShipments as outgoing_shipments' => fn ($query) => $query->whereDate('shipment_date', '>=', $dateFromString)->whereDate('shipment_date', '<=', $dateToString),
                'destinationShipments as incoming_shipments' => fn ($query) => $query->whereDate('shipment_date', '>=', $dateFromString)->whereDate('shipment_date', '<=', $dateToString),
            ])
            ->withSum([
                'originShipments as outgoing_revenue' => fn ($query) => $query
                    ->whereDate('shipment_date', '>=', $dateFromString)
                    ->whereDate('shipment_date', '<=', $dateToString)
                    ->where('status', Shipment::STATUS_DELIVERED),
            ], 'total_price')
            ->orderByDesc('outgoing_shipments')
            ->get();

        $courierPerformance = User::where('role', User::ROLE_COURIER)
            ->withCount([
                'shipments as delivered_shipments' => fn ($query) => $query
                    ->whereDate('shipment_date', '>=', $dateFromString)
                    ->whereDate('shipment_date', '<=', $dateToString)
                    ->where('status', Shipment::STATUS_DELIVERED),
                'shipments as active_shipments' => fn ($query) => $query
                    ->whereDate('shipment_date', '>=', $dateFromString)
                    ->whereDate('shipment_date', '<=', $dateToString)
                    ->whereIn('status', [
                        Shipment::STATUS_PICKED_UP,
                        Shipment::STATUS_IN_TRANSIT,
                        Shipment::STATUS_ARRIVED_AT_BRANCH,
                        Shipment::STATUS_OUT_FOR_DELIVERY,
                    ]),
            ])
            ->orderByDesc('delivered_shipments')
            ->limit(10)
            ->get();

        $recentExceptionTrackings = ShipmentTracking::with(['shipment'])
            ->whereIn('status', [
                ShipmentTracking::STATUS_FAILED_DELIVERY,
                ShipmentTracking::STATUS_EXCEPTION_HOLD,
                ShipmentTracking::STATUS_RETURNED_TO_SENDER,
            ])
            ->whereDate('tracked_at', '>=', $dateFromString)
            ->whereDate('tracked_at', '<=', $dateToString)
            ->whereHas('shipment', function ($query) use ($branchId) {
                $query->when($branchId, function ($subQuery) use ($branchId) {
                    $subQuery->where(function ($innerQuery) use ($branchId) {
                        $innerQuery->where('origin_branch_id', $branchId)
                            ->orWhere('destination_branch_id', $branchId);
                    });
                });
            })
            ->latest('tracked_at')
            ->limit(8)
            ->get();

        return view('admin.reports.manager', [
            'title' => 'Manager Reports',
            'summary' => $summary,
            'exceptionBreakdown' => $exceptionBreakdown,
            'slaAlerts' => $slaAlerts,
            'staleShipments' => $staleShipments,
            'recentExceptionTrackings' => $recentExceptionTrackings,
            'branchPerformance' => $branchPerformance,
            'courierPerformance' => $courierPerformance,
            'branches' => Branch::orderBy('name')->get(),
            'filters' => [
                'date_from' => $dateFromString,
                'date_to' => $dateToString,
                'branch_id' => $branchId,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $this->ensureAccess();

        [$dateFrom, $dateTo] = $this->resolveDateRange(
            $request->input('date_from'),
            $request->input('date_to')
        );
        $dateFromString = $dateFrom->toDateString();
        $dateToString = $dateTo->toDateString();
        $branchId = $request->input('branch_id');

        $branchPerformance = Branch::query()
            ->select('branches.id', 'branches.name', 'branches.city')
            ->when($branchId, fn ($query) => $query->where('branches.id', $branchId))
            ->withCount([
                'originShipments as outgoing_shipments' => fn ($query) => $query->whereDate('shipment_date', '>=', $dateFromString)->whereDate('shipment_date', '<=', $dateToString),
                'destinationShipments as incoming_shipments' => fn ($query) => $query->whereDate('shipment_date', '>=', $dateFromString)->whereDate('shipment_date', '<=', $dateToString),
            ])
            ->withSum([
                'originShipments as outgoing_revenue' => fn ($query) => $query
                    ->whereDate('shipment_date', '>=', $dateFromString)
                    ->whereDate('shipment_date', '<=', $dateToString)
                    ->where('status', Shipment::STATUS_DELIVERED),
            ], 'total_price')
            ->orderBy('name')
            ->get();

        $summary = [
            'total_shipments' => Shipment::query()
                ->whereDate('shipment_date', '>=', $dateFromString)
                ->whereDate('shipment_date', '<=', $dateToString)
                ->when($branchId, function ($query) use ($branchId) {
                    $query->where(function ($subQuery) use ($branchId) {
                        $subQuery->where('origin_branch_id', $branchId)
                            ->orWhere('destination_branch_id', $branchId);
                    });
                })
                ->count(),
            'delivered' => Shipment::query()
                ->where('status', Shipment::STATUS_DELIVERED)
                ->whereDate('shipment_date', '>=', $dateFromString)
                ->whereDate('shipment_date', '<=', $dateToString)
                ->when($branchId, function ($query) use ($branchId) {
                    $query->where(function ($subQuery) use ($branchId) {
                        $subQuery->where('origin_branch_id', $branchId)
                            ->orWhere('destination_branch_id', $branchId);
                    });
                })
                ->count(),
            'in_transit' => Shipment::query()
                ->whereIn('status', [
                    Shipment::STATUS_PICKED_UP,
                    Shipment::STATUS_IN_TRANSIT,
                    Shipment::STATUS_ARRIVED_AT_BRANCH,
                    Shipment::STATUS_OUT_FOR_DELIVERY,
                ])
                ->whereDate('shipment_date', '>=', $dateFromString)
                ->whereDate('shipment_date', '<=', $dateToString)
                ->when($branchId, function ($query) use ($branchId) {
                    $query->where(function ($subQuery) use ($branchId) {
                        $subQuery->where('origin_branch_id', $branchId)
                            ->orWhere('destination_branch_id', $branchId);
                    });
                })
                ->count(),
            'exceptions' => Shipment::query()
                ->whereIn('status', [
                    Shipment::STATUS_FAILED_DELIVERY,
                    Shipment::STATUS_EXCEPTION_HOLD,
                    Shipment::STATUS_RETURNED_TO_SENDER,
                ])
                ->whereDate('shipment_date', '>=', $dateFromString)
                ->whereDate('shipment_date', '<=', $dateToString)
                ->when($branchId, function ($query) use ($branchId) {
                    $query->where(function ($subQuery) use ($branchId) {
                        $subQuery->where('origin_branch_id', $branchId)
                            ->orWhere('destination_branch_id', $branchId);
                    });
                })
                ->count(),
            'paid_amount' => (float) (clone Payment::query())
                ->where('payment_status', Payment::STATUS_PAID)
                ->whereDate('created_at', '>=', $dateFromString)
                ->whereDate('created_at', '<=', $dateToString)
                ->when($branchId, function ($query) use ($branchId) {
                    $query->whereHas('shipment', function ($subQuery) use ($branchId) {
                        $subQuery->where(function ($innerQuery) use ($branchId) {
                            $innerQuery->where('origin_branch_id', $branchId)
                                ->orWhere('destination_branch_id', $branchId);
                        });
                    });
                })
                ->sum('amount'),
            'waiting_payment' => Payment::query()
                ->where('payment_status', Payment::STATUS_PENDING)
                ->whereDate('created_at', '>=', $dateFromString)
                ->whereDate('created_at', '<=', $dateToString)
                ->when($branchId, function ($query) use ($branchId) {
                    $query->whereHas('shipment', function ($subQuery) use ($branchId) {
                        $subQuery->where(function ($innerQuery) use ($branchId) {
                            $innerQuery->where('origin_branch_id', $branchId)
                                ->orWhere('destination_branch_id', $branchId);
                        });
                    });
                })
                ->count(),
        ];

        $selectedBranch = $branchId ? Branch::find($branchId) : null;
        $filename = 'manager-report-' . now()->format('Ymd-His') . '.pdf';
        $pdf = Pdf::loadView('admin.reports.manager_pdf', [
            'summary' => $summary,
            'branchPerformance' => $branchPerformance,
            'filters' => [
                'date_from' => $dateFromString,
                'date_to' => $dateToString,
                'branch_name' => $selectedBranch ? ($selectedBranch->name . ' - ' . $selectedBranch->city) : 'Semua Branch',
            ],
        ])->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    private function ensureAccess(): void
    {
        $role = Auth::user()?->role;

        if (!in_array($role, [User::ROLE_ADMIN, User::ROLE_MANAGER], true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman laporan manager.');
        }
    }

    private function resolveDateRange(?string $dateFrom, ?string $dateTo): array
    {
        $from = $dateFrom ? Carbon::parse($dateFrom) : now()->subDays(30);
        $to = $dateTo ? Carbon::parse($dateTo) : now();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->startOfDay(), $to->endOfDay()];
    }
}
