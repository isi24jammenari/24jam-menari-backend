<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TenantBooking extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function stand()
    {
        return $this->belongsTo(TenantStand::class, 'tenant_stand_id');
    }
}