<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Performance extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'booking_id', 'group_name', 'contact_person', 'cp_name',
        'category', 'supporters', 'works', 'synopsis',
        'arrival_departure', 'music_type', 'instruments',
        'property_setting', 'certificate_names', 'status', 'invitation_number',
    ];

    // Beri tahu Laravel agar kolom JSON dikonversi otomatis jadi Array
    protected $casts = [
        'works'             => 'array',
        'instruments'       => 'array',
        'certificate_names' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}