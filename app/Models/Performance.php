<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Performance extends Model
{
    use HasUuids;

    protected $fillable = [
        'booking_id',
        'group_name',
        'city',
        'contact_name',
        'whatsapp_number',
        'dance_title',
        'status',            // <-- Tambahan baru
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}