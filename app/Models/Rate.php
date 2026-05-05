<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rate extends Model
{
    use HasFactory;

    protected $fillable = [
        'origin_city',
        'destination_city',
        'price_per_kg',
        'estimated_days',
    ];

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }
}