<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function index()
    {
        return view('tracking.index', [
            'title' => 'Track Shipment',
            'shipment' => null,
        ]);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'tracking_number' => 'required|string|max:255',
        ]);

        $shipment = Shipment::with(['sender', 'receiver', 'trackings', 'payment'])
            ->where('tracking_number', $validated['tracking_number'])
            ->first();

        return view('tracking.index', [
            'title' => 'Track Shipment',
            'shipment' => $shipment,
            'searchedTrackingNumber' => $validated['tracking_number'],
        ]);
    }
}
