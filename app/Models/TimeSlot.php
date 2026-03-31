<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'venue_id', 'time_range', 'price', 'is_booked'];

    protected $casts = [
        'is_booked' => 'boolean',
        'price' => 'integer'
    ];

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function booking()
    {
        return $this->hasOne(Booking::class); // 1 Slot = 1 Booking (Jika sukses)
    }
}