<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Booking extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id', 'time_slot_id', 'midtrans_order_id', 
        'amount', 'payment_method', 'status', 'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'amount' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function performance()
    {
        return $this->hasOne(Performance::class); // Formulir Pementasan
    }
}