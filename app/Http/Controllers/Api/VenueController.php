<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Support\Facades\Cache; // Wajib di-import

class VenueController extends Controller
{
    public function index()
    {
        // 1. Caching 15 Detik: Mencegah server mati jika ada 1000 user merefresh halaman bersamaan
        // 2. Select Spesifik: Hanya ambil kolom yang benar-benar dipakai oleh Frontend Zustand
        $venues = Cache::remember('venues_active_slots', 15, function () {
            return Venue::select('id', 'name', 'festival_name')
                ->with(['timeSlots' => function ($query) {
                    $query->select('id', 'venue_id', 'time_range', 'price', 'is_booked')
                          ->orderBy('time_range', 'asc'); // Pastikan jam berurutan
                }])
                ->get();
        });
        
        return $this->successResponse($venues, 'Berhasil mengambil data venue dan slot waktu.');
    }
}