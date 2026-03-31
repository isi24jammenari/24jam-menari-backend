<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    // Beritahu Laravel bahwa ID kita adalah String, bukan Integer Auto-Increment
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'name', 'festival_name'];

    public function timeSlots()
    {
        return $this->hasMany(TimeSlot::class);
    }
}