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
        'contact_person',
        'cp_name',
        'category',
        'supporters',
        'dance_title',
        'duration',
        'synopsis',
        'arrival_departure',
        'music_type',
        'instruments',
        'property_setting',
        'certificate_names',
        'status',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}