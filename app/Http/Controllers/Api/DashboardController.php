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
        // Validasi dasar, butuh parameter 'action' dari frontend ('draft' atau 'submit')
        $request->validate([
            'booking_id' => 'required|uuid|exists:bookings,id',
            'action'     => 'required|string|in:draft,submit', 
        ]);

        // Jika user melakukan 'Submit Final', paksa semua data wajib diisi
        if ($request->action === 'submit') {
            $request->validate([
                'group_name'      => 'required|string|max:255',
                'city'            => 'required|string|max:255',
                'contact_name'    => 'required|string|max:255',
                'whatsapp_number' => 'required|string|max:20',
                'dance_title'     => 'required|string|max:255',
            ]);
        }

        // Pastikan booking ini milik user yang sedang login
        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return $this->errorResponse('Akses ditolak. Jadwal ini bukan milik Anda.', 403);
        }

        // Tentukan status berdasarkan action
        $status = $request->action === 'submit' ? 'completed' : 'draft';

        // Simpan atau Update Data Pementasan (Nullable fields akan aman jika draft)
        $performance = Performance::updateOrCreate(
            ['booking_id' => $booking->id], 
            [
                'group_name'      => $request->group_name,
                'city'            => $request->city,
                'contact_name'    => $request->contact_name,
                'whatsapp_number' => $request->whatsapp_number,
                'dance_title'     => $request->dance_title,
                'status'          => $status
            ]
        );

        $message = $status === 'draft' 
            ? 'Draft berhasil disimpan sementara.' 
            : 'Data pementasan final berhasil disubmit.';

        return $this->successResponse($performance, $message);
    }

    // Menarik jadwal untuk publik (Sangat dibatasi datanya demi privasi)
    public function publicRundown()
    {
        // Tarik semua slot yang sudah dibooking
        $slots = \App\Models\TimeSlot::with([
            'venue', 
            // Join ke booking dan performance, tapi HANYA yang statusnya completed
            'booking.performance' => function ($query) {
                $query->where('status', 'completed');
            }
        ])
        ->where('is_booked', true)
        ->get();

        // Mapping ulang agar data sensitif (seperti email, no HP user) TIDAK ikut terkirim ke publik
        $safeData = $slots->map(function ($slot) {
            $performance = $slot->booking?->performance;
            
            return [
                'venue_name'  => $slot->venue->name ?? 'Venue Tidak Diketahui',
                'time'        => $slot->time_range,
                // Jika data belum final/masih draft, sembunyikan namanya jadi "TBA" (To Be Announced)
                'group_name'  => $performance ? $performance->group_name : 'Menunggu Konfirmasi Data',
                'dance_title' => $performance ? $performance->dance_title : 'TBA',
            ];
        });

        return $this->successResponse($safeData, 'Jadwal publik berhasil ditarik.');
    }
}