<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TenantStand extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function bookings()
    {
        return $this->hasMany(TenantBooking::class);
    }
}