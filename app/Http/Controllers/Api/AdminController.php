<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TimeSlot;
use App\Models\Performance;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    // Mengecek apakah user adalah Admin
    private function isAdmin(Request $request)
    {
        return $request->user()->role === 'admin';
    }

    // Statistik untuk Kotak-Kotak di Dashboard Admin
    public function overview(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return $this->errorResponse('Akses ilegal. Anda bukan admin.', 403);
        }

        // Statistik Dasar
        $totalRevenue = Booking::where('status', 'success')->sum('amount');
        $totalSlots = TimeSlot::count();
        $bookedSlots = TimeSlot::where('is_booked', true)->count();

        // TAMBAHAN: Statistik Kelengkapan Data Karya (Berdasarkan booking yang success)
        $completedPerformances = Performance::where('status', 'completed')
            ->whereHas('booking', function ($query) {
                $query->where('status', 'success');
            })->count();

        $draftPerformances = Performance::where('status', 'draft')
            ->whereHas('booking', function ($query) {
                $query->where('status', 'success');
            })->count();

        // Peserta yang sudah bayar tapi belum menyentuh form sama sekali
        $emptyPerformances = Booking::where('status', 'success')
            ->whereDoesntHave('performance')
            ->count();

        return $this->successResponse([
            'total_revenue' => $totalRevenue,
            'total_slots' => $totalSlots,
            'booked_slots' => $bookedSlots,
            'occupancy_rate' => $totalSlots > 0 ? round(($bookedSlots / $totalSlots) * 100, 1) : 0,
            
            // Data baru untuk kebutuhan Admin
            'data_completion' => [
                'completed' => $completedPerformances,
                'draft' => $draftPerformances,
                'empty' => $emptyPerformances,
                'total_need_action' => $draftPerformances + $emptyPerformances // Total user yang datanya belum beres
            ]
        ], 'Data overview berhasil ditarik.');
    }

    // Menarik Data Keseluruhan untuk Tabel Rundown
    public function rundown(Request $request)
    {
        if (!$this->isAdmin($request)) {
            return $this->errorResponse('Akses ilegal. Anda bukan admin.', 403);
        }

        // Join brutal: Ambil booking, data slot, data venue, data pementasan, dan info akunnya
        $rundown = Booking::with(['user', 'timeSlot.venue', 'performance'])
            ->where('status', 'success')
            ->get();

        // Frontend bisa menggunakan `$item->performance->status` untuk menampilkan badge "Draft" atau "Lengkap" di tabel
        return $this->successResponse($rundown, 'Data rundown berhasil ditarik.');
    }
}