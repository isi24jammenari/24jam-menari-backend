<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Performance;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // Menarik jadwal milik user yang sedang login
    public function mySchedule(Request $request)
    {
        $bookings = Booking::with(['timeSlot.venue', 'performance'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'success')
            ->get();

        return $this->successResponse($bookings, 'Jadwal berhasil diambil.');
    }

    // Menyimpan atau Mengupdate Formulir Pementasan
    public function storePerformance(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|uuid|exists:bookings,id',
            'group_name' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'whatsapp_number' => 'required|string|max:20',
            'dance_title' => 'required|string|max:255',
        ]);

        // Keamanan lapis ekstra: Pastikan booking ini BENAR MILIK user yang sedang login
        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return $this->errorResponse('Akses ditolak. Jadwal ini bukan milik Anda.', 403);
        }

        // Simpan atau Update Data Pementasan
        $performance = Performance::updateOrCreate(
            ['booking_id' => $booking->id], // Cari berdasarkan ini
            $request->only(['group_name', 'city', 'contact_name', 'whatsapp_number', 'dance_title']) // Update data ini
        );

        return $this->successResponse($performance, 'Data pementasan berhasil disimpan.');
    }
}