<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformanceRevision extends Model
{
    protected $guarded = [];
    
    // Casting JSON agar otomatis menjadi array di PHP
    protected $casts = [
        'revised_data' => 'array',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}