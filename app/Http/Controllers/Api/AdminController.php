<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Performance;
use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;

class AdminController extends Controller
{
    /**
     * Tab Overview & Mutasi (Pagination 20 data per page)
     */
    public function getOverview(Request $request)
    {
        $totalIncome = Booking::where('status', 'paid')->sum('amount');
        $totalSlots = TimeSlot::count();
        $bookedSlots = TimeSlot::where('is_booked', true)->count();

        // Mutasi Data
        $mutations = Booking::with(['user:id,name,email', 'timeSlot.venue:id,name'])
            ->where('status', 'paid')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->successResponse([
            'stats' => [
                'total_income'    => $totalIncome,
                'total_slots'     => $totalSlots,
                'booked_slots'    => $bookedSlots,
                'available_slots' => $totalSlots - $bookedSlots,
            ],
            'mutations' => $mutations
        ], 'Berhasil mengambil overview dan mutasi.');
    }

    /**
     * Tab Data Diri & Rundown (Digabung)
     */
    public function getParticipants()
    {
        // Menarik semua relasi sekaligus untuk difilter oleh Excel di Frontend
        $participants = Booking::with(['user', 'timeSlot.venue', 'performance'])
            ->where('status', 'paid')
            ->get();

        return $this->successResponse($participants, 'Berhasil mengambil data seluruh peserta.');
    }

    /**
     * Tab Pengelolaan (Buka/Tutup Akses Download)
     */
    public function toggleCertificateAccess(Request $request)
    {
        $request->validate(['is_open' => 'required|boolean']);
        
        // Simpan status di Cache secara permanen
        Cache::forever('certificate_access_open', $request->is_open);
        
        $statusText = $request->is_open ? 'dibuka' : 'ditutup';
        return $this->successResponse(null, "Akses download E-Sertifikat untuk user telah $statusText.");
    }

    public function getCertificateStatus()
    {
        $isOpen = Cache::get('certificate_access_open', false);
        return $this->successResponse(['is_open' => $isOpen], 'Berhasil mengambil status akses sertifikat.');
    }

    /**
     * Manajemen E-Sertifikat: Statistik
     */
    public function getCertificateStats()
    {
        $completedPerformances = Performance::where('status', 'completed')->get();
        $totalCertificates = 0;

        foreach ($completedPerformances as $perf) {
            if (is_array($perf->certificate_names)) {
                $totalCertificates += count($perf->certificate_names);
            }
        }

        return $this->successResponse([
            'total_valid_groups' => $completedPerformances->count(),
            'total_certificates' => $totalCertificates,
        ], 'Berhasil menghitung statistik E-Sertifikat.');
    }

    /**
     * Manajemen E-Sertifikat: COMPILER ZIP
     */
    public function generateCertificateZip()
    {
        // Bypass time limit karena generate PDF massal sangat memakan waktu
        ini_set('max_execution_time', 300); 

        $performances = Performance::with(['booking.user', 'booking.timeSlot.venue'])
            ->where('status', 'completed')
            ->get();

        if ($performances->isEmpty()) {
            return $this->errorResponse('Belum ada data pementasan yang berstatus Final.', 404);
        }

        $zipFileName = 'E-Sertifikat-24JamMenari-' . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = storage_path('app/public/' . $zipFileName);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            foreach ($performances as $perf) {
                // Bersihkan nama grup agar aman dijadikan nama folder
                $folderName = preg_replace('/[^A-Za-z0-9\- \_]/', '', $perf->group_name ?? 'Grup_Anonim');
                $names = $perf->certificate_names ?? [];

                foreach ($names as $name) {
                    $cleanName = preg_replace('/[^A-Za-z0-9\- \.\_]/', '', $name);
                    
                    // Generate PDF dari blade template (in-memory)
                    $pdf = Pdf::loadView('pdf.certificate', [
                        'name' => $name,
                        'group_name' => $perf->group_name,
                        'venue' => $perf->booking->timeSlot->venue->name ?? 'Venue'
                    ])->setPaper('a4', 'landscape');

                    // Masukkan PDF ke dalam folder spesifik grup di dalam ZIP
                    $fileName = $folderName . '/' . $cleanName . '.pdf';
                    $zip->addFromString($fileName, $pdf->output());
                }
            }
            $zip->close();
        } else {
            return $this->errorResponse('Gagal mengkompilasi file ZIP.', 500);
        }

        // Return file zip dan LANGSUNG HAPUS dari server setelah didownload agar disk tidak penuh
        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
}