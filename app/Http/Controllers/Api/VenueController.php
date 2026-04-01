<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venue;

class VenueController extends Controller
{
    public function index()
    {
        // Ambil semua venue beserta time slots-nya
        $venues = Venue::with('timeSlots')->get();
        
        return $this->successResponse($venues, 'Berhasil mengambil data venue dan slot waktu.');
    }
}