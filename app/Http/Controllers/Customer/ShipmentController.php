<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Rate;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Support\Uploads;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShipmentController extends Controller
{
    public function index()
    {
        $customerId = Auth::guard('customer')->id();
        $search = request('q');
        $status = request('status');

        $shipments = Shipment::with(['sender', 'receiver', 'originBranch', 'destinationBranch', 'payment'])
            ->where(function ($query) use ($customerId) {
                $query->where('sender_id', $customerId)
                    ->orWhere('receiver_id', $customerId);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('tracking_number', 'like', '%' . $search . '%')
                        ->orWhereHas('sender', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
                        ->orWhereHas('receiver', fn ($q) => $q->where('name', 'like', '%' . $search . '%'));
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('customer.shipments.index', [
            'title' => 'My Shipments',
            'shipments' => $shipments,
            'filters' => [
                'q' => $search,
                'status' => $status,
            ],
            'statuses' => Shipment::statuses(),
        ]);
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        $rates = Rate::orderBy('origin_city')
            ->orderBy('destination_city')
            ->orderBy('price_per_kg')
            ->get();
        $couriers = User::where('role', User::ROLE_COURIER)
            ->whereNotNull('branch_id')
            ->orderBy('name')
            ->get();

        $rateByCityRoute = [];
        foreach ($rates as $rate) {
            $cityKey = $this->cityRouteKey($rate->origin_city, $rate->destination_city);
            if (!isset($rateByCityRoute[$cityKey])) {
                $rateByCityRoute[$cityKey] = $rate;
            }
        }

        $routeOptions = [];
        foreach ($branches as $originBranch) {
            foreach ($branches as $destinationBranch) {
                if ((int) $originBranch->id === (int) $destinationBranch->id) {
                    continue;
                }

                $cityKey = $this->cityRouteKey($originBranch->city, $destinationBranch->city);
                if (!isset($rateByCityRoute[$cityKey])) {
                    continue;
                }

                $rate = $rateByCityRoute[$cityKey];
                $routeOptions[$originBranch->id . '-' . $destinationBranch->id] = [
                    'rate_id' => (int) $rate->id,
                    'price_per_kg' => (float) $rate->price_per_kg,
                    'estimated_days' => (int) $rate->estimated_days,
                    'origin_city' => (string) $rate->origin_city,
                    'destination_city' => (string) $rate->destination_city,
                ];
            }
        }

        $couriersByBranch = $couriers
            ->groupBy('branch_id')
            ->map(function ($rows) {
                $pickupCourier = $rows->first(fn ($courier) => $courier->isCourierTaskType(User::COURIER_TASK_PICKUP))
                    ?? $rows->first(fn ($courier) => $courier->courierTaskType() === null);

                if (!$pickupCourier) {
                    return [];
                }

                return [[
                    'id' => (int) $pickupCourier->id,
                    'name' => (string) $pickupCourier->name,
                ]];
            })
            ->toArray();

        return view('customer.shipments.create', [
            'title' => 'Create Shipment',
            'branches' => $branches,
            'routeOptions' => $routeOptions,
            'couriersByBranch' => $couriersByBranch,
            'addressBook' => CustomerAddress::where('customer_id', Auth::guard('customer')->id())
                ->orderByDesc('is_default')
                ->orderBy('label')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $customerId = Auth::guard('customer')->id();

        $validated = $request->validate([
            'receiver_mode' => 'required|in:address,new',
            'address_id' => 'nullable|exists:customer_addresses,id',
            'receiver_label' => 'nullable|string|max:80',
            'save_to_address_book' => 'nullable|boolean',
            'receiver_name' => 'nullable|string|max:50',
            'receiver_email' => 'nullable|email|max:255',
            'receiver_address' => 'nullable|string',
            'receiver_city' => 'nullable|string|max:255',
            'receiver_phone' => 'nullable|string|max:15',
            'origin_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'required|exists:branches,id',
            'shipment_date' => 'required|date',
            'item_name' => 'required|array|min:1',
            'item_name.*' => 'required|string|max:255',
            'quantity' => 'required|array|min:1',
            'quantity.*' => 'required|integer|min:1',
            'weight' => 'required|array|min:1',
            'weight.*' => 'required|numeric|min:0.01',
            'item_photo.*' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'shipment_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ((int) $validated['origin_branch_id'] === (int) $validated['destination_branch_id']) {
            return back()->withErrors([
                'destination_branch_id' => 'Cabang asal dan tujuan tidak boleh sama.',
            ])->withInput();
        }

        if ($validated['receiver_mode'] === 'address' && empty($validated['address_id'])) {
            return back()->withErrors(['address_id' => 'Pilih alamat penerima dari address book.'])->withInput();
        }

        if ($validated['receiver_mode'] === 'new') {
            $request->validate([
                'receiver_name' => 'required|string|max:50',
                'receiver_address' => 'required|string',
                'receiver_city' => 'required|string|max:255',
                'receiver_phone' => 'required|string|max:15',
            ]);
        }

        if (count($validated['item_name']) !== count($validated['quantity']) || count($validated['item_name']) !== count($validated['weight'])) {
            return back()->withErrors(['item_name' => 'Data item tidak sinkron. Pastikan jumlah nama item, qty, dan berat sama.'])->withInput();
        }

        DB::transaction(function () use ($request, $validated, $customerId) {
            if ($validated['receiver_mode'] === 'address') {
                $address = CustomerAddress::where('customer_id', $customerId)
                    ->findOrFail($validated['address_id']);

                $receiverId = $address->receiver_customer_id;
            } else {
                $receiverCustomer = $this->findOrCreateReceiverCustomer(
                    $validated['receiver_name'],
                    $validated['receiver_email'] ?? null,
                    $validated['receiver_address'],
                    $validated['receiver_city'],
                    $validated['receiver_phone'],
                );
                $receiverId = $receiverCustomer->id;

                if (!empty($validated['save_to_address_book'])) {
                    CustomerAddress::create([
                        'customer_id' => $customerId,
                        'receiver_customer_id' => $receiverCustomer->id,
                        'label' => $validated['receiver_label'] ?? ('Alamat ' . $validated['receiver_name']),
                        'receiver_name' => $validated['receiver_name'],
                        'receiver_email' => $validated['receiver_email'] ?? null,
                        'receiver_phone' => $validated['receiver_phone'],
                        'city' => $validated['receiver_city'],
                        'address' => $validated['receiver_address'],
                        'is_default' => false,
                    ]);
                }
            }

            $totalWeight = 0;
            foreach ($validated['item_name'] as $idx => $name) {
                $totalWeight += ((float) $validated['quantity'][$idx] * (float) $validated['weight'][$idx]);
            }
            $originBranch = Branch::findOrFail($validated['origin_branch_id']);
            $destinationBranch = Branch::findOrFail($validated['destination_branch_id']);
            $rate = $this->resolveRateForBranchRoute($originBranch, $destinationBranch);
            $courier = $this->resolveCourierForBranch((int) $originBranch->id);

            if (!$rate) {
                throw ValidationException::withMessages([
                    'origin_branch_id' => 'Rute cabang asal dan tujuan belum punya tarif ongkir.',
                ]);
            }

            if (!$courier) {
                throw ValidationException::withMessages([
                    'origin_branch_id' => 'Belum ada courier pickup aktif untuk cabang asal yang dipilih.',
                ]);
            }

            $totalPrice = $totalWeight * (float) $rate->price_per_kg;

            $shipmentData = [
                'tracking_number' => $this->generateTrackingNumber(),
                'sender_id' => $customerId,
                'receiver_id' => $receiverId,
                'origin_branch_id' => $validated['origin_branch_id'],
                'destination_branch_id' => $validated['destination_branch_id'],
                'courier_id' => $courier->id,
                'rate_id' => $rate->id,
                'total_weight' => $totalWeight,
                'total_price' => $totalPrice,
                'status' => Shipment::STATUS_PENDING,
                'shipment_date' => $validated['shipment_date'],
            ];

            if ($request->hasFile('shipment_photo')) {
                $shipmentData['photo'] = Uploads::storePublic($request->file('shipment_photo'), 'shipments');
            }

            $shipment = Shipment::create($shipmentData);

            $itemPhotos = $request->file('item_photo', []);
            foreach ($validated['item_name'] as $idx => $name) {
                $itemData = [
                    'shipment_id' => $shipment->id,
                    'item_name' => $name,
                    'quantity' => $validated['quantity'][$idx],
                    'weight' => $validated['weight'][$idx],
                ];

                if (!empty($itemPhotos[$idx])) {
                    $itemData['photo'] = Uploads::storePublic($itemPhotos[$idx], 'shipment-items', 'item_' . $idx);
                }

                ShipmentItem::create($itemData);
            }
        });

        return redirect()->route('customer.shipments.index')->with('success', 'Shipment berhasil dibuat.');
    }

    public function show(Shipment $customerShipment)
    {
        $customerId = Auth::guard('customer')->id();

        if ($customerShipment->sender_id !== $customerId && $customerShipment->receiver_id !== $customerId) {
            abort(403, 'Anda tidak memiliki akses ke shipment ini.');
        }

        return view('customer.shipments.show', [
            'title' => 'Shipment Detail',
            'shipment' => $customerShipment->load(['sender', 'receiver', 'originBranch', 'destinationBranch', 'courier', 'items', 'trackings', 'payment']),
            'statusFlow' => Shipment::deliveryFlow(),
            'currentStep' => $customerShipment->statusStep(),
        ]);
    }

    private function generateTrackingNumber(): string
    {
        do {
            $tracking = 'EXP' . now()->format('Ymd') . strtoupper(Str::random(6));
        } while (Shipment::where('tracking_number', $tracking)->exists());

        return $tracking;
    }

    private function findOrCreateReceiverCustomer(
        string $name,
        ?string $email,
        string $address,
        string $city,
        string $phone
    ): Customer {
        if (!empty($email)) {
            $existing = Customer::where('email', $email)->first();
            if ($existing) {
                $existing->update([
                    'name' => $name,
                    'address' => $address,
                    'city' => $city,
                    'phone' => $phone,
                ]);

                return $existing;
            }
        }

        $generatedEmail = $email ?: 'receiver+' . Str::lower(Str::random(10)) . '@ekspedisi.local';

        return Customer::create([
            'name' => $name,
            'email' => $generatedEmail,
            'password' => Hash::make(Str::random(16)),
            'address' => $address,
            'city' => $city,
            'phone' => $phone,
        ]);
    }

    private function resolveRateForBranchRoute(Branch $originBranch, Branch $destinationBranch): ?Rate
    {
        return Rate::query()
            ->whereRaw('LOWER(origin_city) = ?', [mb_strtolower((string) $originBranch->city)])
            ->whereRaw('LOWER(destination_city) = ?', [mb_strtolower((string) $destinationBranch->city)])
            ->orderBy('price_per_kg')
            ->orderBy('estimated_days')
            ->first();
    }

    private function resolveCourierForBranch(int $originBranchId): ?User
    {
        return User::resolveCourierForTask($originBranchId, User::COURIER_TASK_PICKUP);
    }

    private function cityRouteKey(string $originCity, string $destinationCity): string
    {
        return mb_strtolower(trim($originCity)) . '|' . mb_strtolower(trim($destinationCity));
    }
}
