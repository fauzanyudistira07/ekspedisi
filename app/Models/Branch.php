<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'city',
        'address',
        'phone',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function originShipments()
    {
        return $this->hasMany(Shipment::class, 'origin_branch_id');
    }

    public function destinationShipments()
    {
        return $this->hasMany(Shipment::class, 'destination_branch_id');
    }

    public function manifests()
    {
        return $this->hasMany(ShipmentManifest::class);
    }
}
