<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids; 
use Laravel\Sanctum\HasApiTokens; 

class User extends Authenticatable
{
    // 2. TAMBAHKAN HasApiTokens DI SINI
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected $fillable = [
        'name', 'email', 'password', 'role'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // 1 User bisa punya banyak Bookings
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}