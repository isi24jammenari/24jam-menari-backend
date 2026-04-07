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
use App\Models\PerformanceRevision;

class AdminController extends Controller
{
    /**
     * Tab Overview & Mutasi (Tanpa Pagination)
     */
    public function getOverview(Request $request)
    {
        $totalIncome = Booking::where('status', 'success')->sum('amount');
        $totalSlots = TimeSlot::count();
        $bookedSlots = TimeSlot::where('is_booked', true)->count();

        // Tarik semua data secara langsung (Get All)
        $mutations = Booking::with(['user:id,name,email', 'timeSlot.venue:id,name,festival_name', 'performance'])
            ->where('status', 'success')
            ->orderBy('created_at', 'desc')
            ->get(); // <-- UBAH: paginate(20) dihapus dan diganti menjadi get()

        // INJEKSI VIRTUAL MUTASI
        $mutations->transform(function ($booking) { // <-- UBAH: getCollection() dihapus karena sudah bukan paginator
            if (!$booking->user) {
                $booking->user_id = 'orphan-id';
                $booking->setRelation('user', new \App\Models\User([
                    'id' => 'orphan-id',
                    'name' => 'BELUM KLAIM AKUN',
                    'email' => 'Menunggu Klaim'
                ]));
            }
            
            // INJEKSI PERFORMANCE MUTLAK (Pencegah Error UI)
            if (!$booking->performance) {
                $booking->setRelation('performance', new \App\Models\Performance([
                    'id' => 'perf-orphan',
                    'booking_id' => $booking->id,
                    'group_name' => 'BELUM ISI FORMULIR',
                    'status' => 'completed' // BYPASS FILTER FRONTEND
                ]));
            }

            return $booking;
        });

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
        $participants = Booking::with(['user', 'timeSlot.venue', 'performance'])
            ->where('status', 'success')
            ->get();

        // INJEKSI GANDA: Bypass filter Excel dan Rundown
        $participants->transform(function ($booking) {
            
            // 1. Bypass User
            if (!$booking->user) {
                $booking->user_id = 'orphan-id';
                $booking->setRelation('user', new \App\Models\User([
                    'id' => 'orphan-id',
                    'name' => 'BELUM KLAIM AKUN',
                    'email' => 'Menunggu Klaim'
                ]));
            }

            // 2. Bypass Performance (SANGAT KRUSIAL UNTUK RUNDOWN EXCEL)
            if (!$booking->performance) {
                $booking->setRelation('performance', new \App\Models\Performance([
                    'id' => 'perf-orphan-' . $booking->id,
                    'booking_id' => $booking->id,
                    'group_name' => 'BELUM ISI FORM / TOKEN: ' . $booking->midtrans_order_id,
                    'category' => '-',
                    'contact_person' => '-',
                    'cp_name' => '-',
                    'supporters' => '-',
                    'works' => '[]',
                    'status' => 'completed', // STATUS INI YANG MEMAKSA EXCEL MENAMPILKANNYA
                    'invitation_number' => null
                ]));
            }

            return $booking;
        });

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

    /**
     * ==========================================
     * MANAJEMEN REVISI FORMULIR USER
     * ==========================================
     */
    
    // Tarik semua permintaan revisi yang statusnya pending
    public function getPendingRevisions()
    {
        $revisions = PerformanceRevision::with([
            'booking.user:id,name,email,phone',
            'booking.timeSlot.venue:id,name',
            'booking.performance' // Untuk komparasi data asli vs data baru di frontend
        ])->where('status', 'pending')
          ->orderBy('created_at', 'asc')
          ->get();

        return $this->successResponse($revisions, 'Berhasil mengambil daftar permintaan revisi.');
    }

    // Setujui dan Timpa Database Utama
    public function approveRevision($id)
    {
        $revision = PerformanceRevision::findOrFail($id);
        
        if ($revision->status !== 'pending') {
            return $this->errorResponse('Revisi ini sudah diproses sebelumnya.', 400);
        }

        // Timpa data utama di tabel performances
        $dataToApply = $revision->revised_data;
        $dataToApply['status'] = 'completed'; // Pastikan statusnya final
        
        Performance::updateOrCreate(
            ['booking_id' => $revision->booking_id],
            $dataToApply
        );

        // Tandai revisi sebagai disetujui
        $revision->update(['status' => 'approved']);

        return $this->successResponse(null, 'Permintaan perubahan data berhasil disetujui dan diterapkan ke database utama.');
    }

    // Tolak Revisi
    public function rejectRevision($id)
    {
        $revision = PerformanceRevision::findOrFail($id);
        $revision->update(['status' => 'rejected']);

        return $this->successResponse(null, 'Permintaan perubahan data berhasil ditolak.');
    }
}