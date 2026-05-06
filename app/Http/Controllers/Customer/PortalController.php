<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Models\Payment;
use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\ShipmentTracking;

class PortalController extends Controller
{
    public function index()
    {
        $customerId = Auth::guard('customer')->id();
        $addressCount = CustomerAddress::where('customer_id', $customerId)->count();

        $baseQuery = Shipment::query()->where(function ($query) use ($customerId) {
            $query->where('sender_id', $customerId)
                ->orWhere('receiver_id', $customerId);
        });

        $needPaymentCount = Shipment::query()
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
            ->count();

        return view('home.index', [
            'title' => 'Home',
            'stats' => [
                'total' => (clone $baseQuery)->count(),
                'pending' => (clone $baseQuery)->where('status', Shipment::STATUS_PENDING)->count(),
                'in_transit' => (clone $baseQuery)->whereIn('status', [
                    Shipment::STATUS_PICKED_UP,
                    Shipment::STATUS_IN_TRANSIT,
                    Shipment::STATUS_ARRIVED_AT_BRANCH,
                    Shipment::STATUS_OUT_FOR_DELIVERY,
                ])->count(),
                'delivered' => (clone $baseQuery)->where('status', Shipment::STATUS_DELIVERED)->count(),
                'need_payment' => Payment::whereHas('shipment', function ($query) use ($customerId) {
                    $query->where('sender_id', $customerId);
                })->where('payment_status', Payment::STATUS_PENDING)->count(),
                'address_book' => $addressCount,
            ],
            'recentShipments' => (clone $baseQuery)
                ->with(['sender', 'receiver'])
                ->latest()
                ->limit(5)
                ->get(),
            'workflow' => [
                'Buat Shipment: Isi data penerima, cabang asal/tujuan, item, dan jadwal kirim.',
                'Cek Resi: Sistem akan membuat nomor resi otomatis setelah shipment tersimpan.',
                'Bayar Kiriman: Buka checkout Midtrans dan pilih kanal pembayaran yang tersedia.',
                'Pantau Tracking: Lihat update status hingga paket delivered.',
            ],
            'quickActions' => [
                [
                    'title' => 'Lengkapi Address Book',
                    'desc' => $addressCount > 0 ? 'Address book kamu sudah aktif untuk mempercepat order berikutnya.' : 'Simpan alamat penerima favorit supaya checkout lebih cepat.',
                    'route' => route('customer.addresses.index'),
                    'button' => $addressCount > 0 ? 'Kelola Address Book' : 'Tambah Alamat',
                    'highlight' => $addressCount === 0,
                ],
                [
                    'title' => 'Shipment Perlu Pembayaran',
                    'desc' => $needPaymentCount > 0
                        ? 'Ada ' . $needPaymentCount . ' shipment yang belum dibayar atau perlu retry pembayaran.'
                        : 'Semua shipment pengirim kamu sudah memiliki pembayaran aktif.',
                    'route' => route('customer.payments.create'),
                    'button' => 'Bayar Sekarang',
                    'highlight' => $needPaymentCount > 0,
                ],
                [
                    'title' => 'Pantau Status Kiriman',
                    'desc' => 'Buka riwayat shipment untuk cek timeline update paling terbaru.',
                    'route' => route('customer.shipments.index'),
                    'button' => 'Lihat Shipment',
                    'highlight' => false,
                ],
            ],
        ]);
    }

    public function about()
    {
        return view('home.about', ['title' => 'About']);
    }

    public function service()
    {
        return view('home.service', ['title' => 'Service']);
    }

    public function blog()
    {
        return view('home.blog', ['title' => 'Blog']);
    }

    public function contact()
    {
        return view('home.contact', ['title' => 'Contact']);
    }

    public function markTrackingNotificationsRead(): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        $customer->update([
            'last_tracking_seen_at' => now(),
        ]);

        return back()->with('success', 'Notifikasi tracking sudah ditandai dibaca.');
    }

    public function pollTrackingNotifications(): JsonResponse
    {
        $customer = Auth::guard('customer')->user();
        $seenAt = $customer->last_tracking_seen_at;

        $baseQuery = ShipmentTracking::with('shipment')
            ->whereHas('shipment', function ($query) use ($customer) {
                $query->where('sender_id', $customer->id)
                    ->orWhere('receiver_id', $customer->id);
            });

        $unreadCount = (clone $baseQuery)
            ->when($seenAt, fn ($query) => $query->where('tracked_at', '>', $seenAt))
            ->count();

        $latestItems = $baseQuery->latest('tracked_at')->limit(5)->get()->map(function ($item) {
            return [
                'shipment_id' => $item->shipment_id,
                'tracking_number' => $item->shipment->tracking_number ?? '-',
                'status' => strtoupper(str_replace('_', ' ', $item->status)),
                'location' => $item->location,
                'tracked_at' => optional($item->tracked_at)->format('d M Y H:i'),
                'url' => route('customer.shipments.show', $item->shipment_id),
            ];
        });

        return response()->json([
            'unread_count' => $unreadCount,
            'items' => $latestItems,
        ]);
    }
}
