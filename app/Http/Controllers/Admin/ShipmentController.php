<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AuthorizesRoleTableAccess;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Rate;
use App\Models\Shipment;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\Uploads;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ShipmentController extends Controller
{
    use AuthorizesRoleTableAccess;

    public function index()
    {
        $this->ensureTablePermission('shipments', 'read');

        $user = Auth::user();
        $search = request('q');
        $status = request('status');
        $courierId = request('courier_id');

        $shipments = Shipment::with(['sender', 'receiver', 'courier', 'originBranch', 'destinationBranch', 'rate'])
            ->when($user->role === User::ROLE_COURIER, function ($query) use ($user) {
                $query->where('courier_id', $user->id);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('tracking_number', 'like', '%' . $search . '%')
                        ->orWhereHas('sender', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('receiver', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($courierId && $user->role !== User::ROLE_COURIER, fn ($query) => $query->where('courier_id', $courierId))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $summaryBaseQuery = Shipment::query()
            ->when($user->role === User::ROLE_COURIER, fn ($query) => $query->where('courier_id', $user->id));

        return view('admin.shipments.index', [
            'title' => 'Shipments',
            'shipments' => $shipments,
            'summary' => [
                'total' => (clone $summaryBaseQuery)->count(),
                'pending' => (clone $summaryBaseQuery)->where('status', Shipment::STATUS_PENDING)->count(),
                'in_transit' => (clone $summaryBaseQuery)->whereIn('status', [
                    Shipment::STATUS_PICKED_UP,
                    Shipment::STATUS_IN_TRANSIT,
                    Shipment::STATUS_ARRIVED_AT_BRANCH,
                    Shipment::STATUS_OUT_FOR_DELIVERY,
                    Shipment::STATUS_EXCEPTION_HOLD,
                    Shipment::STATUS_FAILED_DELIVERY,
                ])->count(),
                'delivered' => (clone $summaryBaseQuery)->where('status', Shipment::STATUS_DELIVERED)->count(),
                'exception' => (clone $summaryBaseQuery)->whereIn('status', [
                    Shipment::STATUS_FAILED_DELIVERY,
                    Shipment::STATUS_EXCEPTION_HOLD,
                    Shipment::STATUS_RETURNED_TO_SENDER,
                ])->count(),
            ],
            'filters' => [
                'q' => $search,
                'status' => $status,
                'courier_id' => $courierId,
            ],
            'statuses' => Shipment::statuses(),
            'couriers' => User::where('role', User::ROLE_COURIER)->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $this->ensureTablePermission('shipments', 'create');

        return view('admin.shipments.create', [
            'title' => 'Create Shipment',
            'customers' => Customer::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'couriers' => User::where('role', User::ROLE_COURIER)->orderBy('name')->get(),
            'rates' => Rate::orderBy('origin_city')->orderBy('destination_city')->get(),
            'statuses' => Shipment::statuses(),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureTablePermission('shipments', 'create');

        $validated = $request->validate([
            'tracking_number' => 'required|string|max:255|unique:shipments,tracking_number',
            'sender_id' => 'required|exists:customers,id',
            'receiver_id' => 'required|exists:customers,id',
            'origin_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'required|exists:branches,id',
            'courier_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_COURIER)),
            ],
            'rate_id' => 'required|exists:rates,id',
            'total_weight' => 'required|numeric|min:0.01',
            'status' => ['required', Rule::in(Shipment::statuses())],
            'shipment_date' => 'required|date',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $validated['total_price'] = $this->resolveTotalPrice(
            $validated['origin_branch_id'],
            $validated['destination_branch_id'],
            $validated['rate_id'],
            (float) $validated['total_weight'],
        );
        $validated['courier_id'] = $this->resolveAutomaticCourierAssignment($validated, $validated['courier_id']);

        if ($request->hasFile('photo')) {
            $validated['photo'] = Uploads::storePublic($request->file('photo'), 'shipments');
        }

        $shipment = Shipment::create($validated);
        AuditLogger::log($shipment, 'shipment.created', 'Shipment ' . $shipment->tracking_number . ' dibuat.', null, $shipment->only([
            'tracking_number',
            'status',
            'origin_branch_id',
            'destination_branch_id',
            'courier_id',
            'total_weight',
            'total_price',
        ]));

        return redirect()->route('shipments.index')->with('success', 'Shipment berhasil ditambahkan.');
    }

    public function show(Shipment $shipment)
    {
        $this->ensureTablePermission('shipments', 'read');
        $this->ensureShipmentVisibility($shipment);

        return view('admin.shipments.show', [
            'title' => 'Shipment Detail',
            'shipment' => $shipment->load(['sender', 'receiver', 'courier', 'originBranch', 'destinationBranch', 'rate', 'items', 'trackings.branch', 'payment', 'auditLogs.actor']),
        ]);
    }

    public function label(Request $request, Shipment $shipment)
    {
        $this->ensureTablePermission('shipments', 'read');
        $this->ensureShipmentVisibility($shipment);

        $shipment->load(['sender', 'receiver', 'originBranch', 'destinationBranch', 'courier', 'items']);

        $viewData = [
            'shipment' => $shipment,
            'barcodeRows' => $this->buildLabelBarcodeRows($shipment->tracking_number),
        ];

        if ($request->boolean('preview')) {
            return view('admin.shipments.label_preview', $viewData);
        }

        $pdf = Pdf::loadView('admin.shipments.label', $viewData)->setPaper([0, 0, 425.2, 283.46]);

        return $pdf->stream('label-' . $shipment->tracking_number . '.pdf');
    }

    public function edit(Shipment $shipment)
    {
        $this->ensureTablePermission('shipments', 'update');
        $this->ensureShipmentVisibility($shipment);

        return view('admin.shipments.edit', [
            'title' => 'Edit Shipment',
            'shipment' => $shipment,
            'customers' => Customer::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'couriers' => User::where('role', User::ROLE_COURIER)->orderBy('name')->get(),
            'rates' => Rate::orderBy('origin_city')->orderBy('destination_city')->get(),
            'statuses' => Shipment::statuses(),
        ]);
    }

    public function update(Request $request, Shipment $shipment)
    {
        $this->ensureTablePermission('shipments', 'update');
        $this->ensureShipmentVisibility($shipment);

        $validated = $request->validate([
            'tracking_number' => 'required|string|max:255|unique:shipments,tracking_number,' . $shipment->id,
            'sender_id' => 'required|exists:customers,id',
            'receiver_id' => 'required|exists:customers,id',
            'origin_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'required|exists:branches,id',
            'courier_id' => [
                'required',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_COURIER)),
            ],
            'rate_id' => 'required|exists:rates,id',
            'total_weight' => 'required|numeric|min:0.01',
            'status' => ['required', Rule::in(Shipment::statuses())],
            'shipment_date' => 'required|date',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $validated['total_price'] = $this->resolveTotalPrice(
            $validated['origin_branch_id'],
            $validated['destination_branch_id'],
            $validated['rate_id'],
            (float) $validated['total_weight'],
        );
        $validated['courier_id'] = $this->resolveAutomaticCourierAssignment($validated, $validated['courier_id']);

        if ($request->hasFile('photo')) {
            $validated['photo'] = Uploads::storePublic($request->file('photo'), 'shipments');
        }

        $oldValues = $shipment->only(['status', 'courier_id', 'origin_branch_id', 'destination_branch_id', 'total_weight', 'total_price']);
        $shipment->update($validated);
        AuditLogger::log($shipment, 'shipment.updated', 'Shipment ' . $shipment->tracking_number . ' diperbarui.', $oldValues, $shipment->only([
            'status',
            'courier_id',
            'origin_branch_id',
            'destination_branch_id',
            'total_weight',
            'total_price',
        ]));

        return redirect()->route('shipments.index')->with('success', 'Shipment berhasil diperbarui.');
    }

    public function destroy(Shipment $shipment)
    {
        $this->ensureTablePermission('shipments', 'delete');

        $shipment->delete();

        return redirect()->route('shipments.index')->with('success', 'Shipment berhasil dihapus.');
    }

    private function ensureShipmentVisibility(Shipment $shipment): void
    {
        $user = Auth::user();

        if ($user && $user->role === User::ROLE_COURIER && $shipment->courier_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke shipment ini.');
        }
    }

    private function resolveTotalPrice(int $originBranchId, int $destinationBranchId, int $rateId, float $totalWeight): float
    {
        $originBranch = Branch::findOrFail($originBranchId);
        $destinationBranch = Branch::findOrFail($destinationBranchId);
        $rate = Rate::findOrFail($rateId);

        if ($originBranch->id === $destinationBranch->id) {
            throw ValidationException::withMessages([
                'destination_branch_id' => 'Cabang asal dan tujuan tidak boleh sama.',
            ]);
        }

        if (
            strcasecmp((string) $originBranch->city, (string) $rate->origin_city) !== 0 ||
            strcasecmp((string) $destinationBranch->city, (string) $rate->destination_city) !== 0
        ) {
            throw ValidationException::withMessages([
                'rate_id' => 'Rate yang dipilih tidak sesuai dengan cabang asal/tujuan.',
            ]);
        }

        return round($totalWeight * (float) $rate->price_per_kg, 2);
    }

    private function buildLabelBarcodeRows(string $trackingNumber): array
    {
        $rows = [];

        foreach (str_split($trackingNumber) as $character) {
            $binary = str_pad(decbin(ord($character)), 8, '0', STR_PAD_LEFT);
            $rows[] = str_replace(['0', '1'], ['1', '3'], $binary);
        }

        return $rows;
    }

    private function resolveAutomaticCourierAssignment(array $validated, int|string $fallbackCourierId): int
    {
        if (in_array($validated['status'], [
            Shipment::STATUS_EXCEPTION_HOLD,
            Shipment::STATUS_FAILED_DELIVERY,
            Shipment::STATUS_RETURNED_TO_SENDER,
            Shipment::STATUS_CANCELLED,
        ], true)) {
            return (int) $fallbackCourierId;
        }

        $shipment = new Shipment([
            'origin_branch_id' => $validated['origin_branch_id'],
            'destination_branch_id' => $validated['destination_branch_id'],
            'status' => $validated['status'],
        ]);

        $courier = $shipment->resolveResponsibleCourierForStatus($validated['status']);

        if ($courier) {
            return (int) $courier->id;
        }

        throw ValidationException::withMessages([
            'courier_id' => match ($validated['status']) {
                Shipment::STATUS_PENDING => 'Belum ada courier pickup aktif di cabang asal.',
                Shipment::STATUS_PICKED_UP,
                Shipment::STATUS_IN_TRANSIT => 'Belum ada courier HTH aktif di cabang asal.',
                Shipment::STATUS_ARRIVED_AT_BRANCH,
                Shipment::STATUS_OUT_FOR_DELIVERY,
                Shipment::STATUS_DELIVERED => 'Belum ada courier drop aktif di cabang tujuan.',
                default => 'Belum ada courier yang sesuai untuk status shipment ini.',
            },
        ]);
    }
}
