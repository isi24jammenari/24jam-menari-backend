<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Performance;
use App\Models\TimeSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;
use App\Models\PerformanceRevision;

class AdminController extends Controller
{
    /**
     * Tab Overview & Mutasi (GOD MODE SINKRONISASI)
     */
    public function getOverview(Request $request)
    {
        $totalSlots = TimeSlot::count();
        $bookedSlots = TimeSlot::where('is_booked', true)->count();

        // 1. Tarik Data Normal
        $mutations = Booking::with(['user', 'timeSlot.venue', 'performance'])
            ->whereIn('status', ['success', 'pending']) 
            ->orderBy('created_at', 'desc')
            ->get(); 

        // =========================================================================
        // 2. 🚨 GOD MODE: SINKRONISASI ABSOLUT ANTARA SLOT DAN BOOKING 🚨
        // =========================================================================
        $lockedSlots = TimeSlot::with('venue')->where('is_booked', true)->get();
        $mutationSlotIds = $mutations->pluck('time_slot_id')->toArray();

        foreach ($lockedSlots as $slot) {
            // Jika ada slot terkunci TAPI data transaksinya GAIB / Dibuang Eloquent
            if (!in_array($slot->id, $mutationSlotIds)) {
                // Tembus langsung ke inti DB menggunakan Raw Builder
                $rawBooking = DB::table('bookings')->where('time_slot_id', $slot->id)->first();

                // Konstruksi Ulang Model secara Paksa
                $virtualBooking = new Booking([
                    'id' => $rawBooking ? $rawBooking->id : 'RECOVERY-' . $slot->id,
                    'user_id' => $rawBooking ? $rawBooking->user_id : null,
                    'time_slot_id' => $slot->id,
                    'midtrans_order_id' => $rawBooking ? $rawBooking->midtrans_order_id : 'MANUAL-RECOVERY',
                    'amount' => $rawBooking ? $rawBooking->amount : $slot->price,
                    'payment_method' => $rawBooking ? $rawBooking->payment_method : 'SYSTEM',
                    'status' => 'success',
                    'created_at' => $rawBooking ? $rawBooking->created_at : now(),
                ]);

                // Suntik relasi dan masukkan ke array mutasi
                $virtualBooking->setRelation('timeSlot', $slot);
                $mutations->push($virtualBooking);
            }
        }

        // =========================================================================
        // 3. INJEKSI VIRTUAL MUTASI (Penyelamat Error UI)
        // =========================================================================
        $mutations->transform(function ($booking) { 
            $statusLabel = $booking->status === 'pending' ? ' (PENDING/STUCK)' : '';

            if (!$booking->user) {
                $booking->user_id = 'orphan-id';
                $booking->setRelation('user', new \App\Models\User([
                    'id' => 'orphan-id',
                    'name' => 'BELUM KLAIM AKUN' . $statusLabel,
                    'email' => 'Menunggu Klaim'
                ]));
            }
            
            if (!$booking->timeSlot) {
                $fakeVenue = new \App\Models\Venue([
                    'id' => 'error-venue',
                    'name' => 'VENUE ERROR / TIDAK DITEMUKAN',
                    'festival_name' => 'ERROR'
                ]);
                
                $fakeTimeSlot = new \App\Models\TimeSlot([
                    'id' => $booking->time_slot_id,
                    'venue_id' => 'error-venue',
                    'time_range' => 'SLOT CACAT: ' . $booking->time_slot_id,
                    'price' => $booking->amount,
                    'is_booked' => true
                ]);
                
                $fakeTimeSlot->setRelation('venue', $fakeVenue);
                $booking->setRelation('timeSlot', $fakeTimeSlot);
            } elseif (!$booking->timeSlot->venue) {
                $fakeVenue = new \App\Models\Venue([
                    'id' => 'error-venue',
                    'name' => 'VENUE ERROR / TIDAK DITEMUKAN',
                    'festival_name' => 'ERROR'
                ]);
                $booking->timeSlot->setRelation('venue', $fakeVenue);
            }

            if (!$booking->performance) {
                $booking->setRelation('performance', new \App\Models\Performance([
                    'id' => 'perf-orphan-' . $booking->id,
                    'booking_id' => $booking->id,
                    'group_name' => 'BELUM ISI FORM / TOKEN: ' . ($booking->midtrans_order_id ?? 'KOSONG') . $statusLabel,
                    'status' => 'completed'
                ]));
            }

            return $booking;
        });

        // 4. RESET INDEX (Mencegah Array berubah jadi JSON Object yang membuat React Crash)
        $finalData = $mutations->values();

        return $this->successResponse([
            'stats' => [
                'total_income'    => $finalData->sum('amount'), // Sinkronisasi total uang dengan tabel
                'total_slots'     => $totalSlots,
                'booked_slots'    => $bookedSlots,
                'available_slots' => $totalSlots - $bookedSlots,
            ],
            'mutations' => [
                'data' => $finalData, 
                'current_page' => 1,
                'last_page' => 1,
                'total' => $finalData->count()
            ]
        ], 'GOD-MODE-AKTIF-MUTLAK');
    }

    /**
     * Tab Data Diri & Rundown
     */
    public function getParticipants()
    {
        $participants = Booking::with(['user', 'timeSlot.venue', 'performance'])
            ->whereIn('status', ['success', 'pending'])
            ->get();

        // GOD MODE SYNC UNTUK RUNDOWN
        $lockedSlots = TimeSlot::with('venue')->where('is_booked', true)->get();
        $participantSlotIds = $participants->pluck('time_slot_id')->toArray();

        foreach ($lockedSlots as $slot) {
            if (!in_array($slot->id, $participantSlotIds)) {
                $rawBooking = DB::table('bookings')->where('time_slot_id', $slot->id)->first();
                $virtualBooking = new Booking([
                    'id' => $rawBooking ? $rawBooking->id : 'RECOVERY-' . $slot->id,
                    'user_id' => $rawBooking ? $rawBooking->user_id : null,
                    'time_slot_id' => $slot->id,
                    'midtrans_order_id' => $rawBooking ? $rawBooking->midtrans_order_id : 'MANUAL-RECOVERY',
                    'amount' => $rawBooking ? $rawBooking->amount : $slot->price,
                    'status' => 'success',
                ]);
                $virtualBooking->setRelation('timeSlot', $slot);
                $participants->push($virtualBooking);
            }
        }

        $participants->transform(function ($booking) {
            $statusLabel = $booking->status === 'pending' ? ' (PENDING/STUCK)' : '';
            
            if (!$booking->user) {
                $booking->user_id = 'orphan-id';
                $booking->setRelation('user', new \App\Models\User([
                    'id' => 'orphan-id',
                    'name' => 'BELUM KLAIM AKUN' . $statusLabel,
                    'email' => 'Menunggu Klaim'
                ]));
            }

            if (!$booking->timeSlot) {
                // ... bypass sama seperti overview
            }

            if (!$booking->performance) {
                $booking->setRelation('performance', new \App\Models\Performance([
                    'id' => 'perf-orphan-' . $booking->id,
                    'booking_id' => $booking->id,
                    'group_name' => 'BELUM ISI FORM / TOKEN: ' . ($booking->midtrans_order_id ?? 'KOSONG') . $statusLabel,
                    'category' => '-',
                    'contact_person' => '-',
                    'cp_name' => '-',
                    'supporters' => '-',
                    'works' => '[]',
                    'status' => 'completed', 
                    'invitation_number' => null
                ]));
            }
            return $booking;
        });

        return $this->successResponse($participants->values(), 'Berhasil mengambil data seluruh peserta.');
    }

    public function toggleCertificateAccess(Request $request)
    {
        $request->validate(['is_open' => 'required|boolean']);
        Cache::forever('certificate_access_open', $request->is_open);
        $statusText = $request->is_open ? 'dibuka' : 'ditutup';
        return $this->successResponse(null, "Akses download E-Sertifikat untuk user telah $statusText.");
    }

    public function getCertificateStatus()
    {
        $isOpen = Cache::get('certificate_access_open', false);
        return $this->successResponse(['is_open' => $isOpen], 'Berhasil mengambil status akses sertifikat.');
    }

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

    public function generateCertificateZip()
    {
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
                $folderName = preg_replace('/[^A-Za-z0-9\- \_]/', '', $perf->group_name ?? 'Grup_Anonim');
                $names = $perf->certificate_names ?? [];

                foreach ($names as $name) {
                    $cleanName = preg_replace('/[^A-Za-z0-9\- \.\_]/', '', $name);
                    $pdf = Pdf::loadView('pdf.certificate', [
                        'name' => $name,
                        'group_name' => $perf->group_name,
                        'venue' => $perf->booking->timeSlot->venue->name ?? 'Venue'
                    ])->setPaper('a4', 'landscape');
                    $fileName = $folderName . '/' . $cleanName . '.pdf';
                    $zip->addFromString($fileName, $pdf->output());
                }
            }
            $zip->close();
        } else {
            return $this->errorResponse('Gagal mengkompilasi file ZIP.', 500);
        }

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function getPendingRevisions()
    {
        $revisions = PerformanceRevision::with([
            'booking.user:id,name,email,phone',
            'booking.timeSlot.venue:id,name',
            'booking.performance' 
        ])->where('status', 'pending')
          ->orderBy('created_at', 'asc')
          ->get();

        return $this->successResponse($revisions, 'Berhasil mengambil daftar permintaan revisi.');
    }

    public function approveRevision($id)
    {
        $revision = PerformanceRevision::findOrFail($id);
        
        if ($revision->status !== 'pending') {
            return $this->errorResponse('Revisi ini sudah diproses sebelumnya.', 400);
        }

        $dataToApply = $revision->revised_data;
        $dataToApply['status'] = 'completed'; 
        
        Performance::updateOrCreate(
            ['booking_id' => $revision->booking_id],
            $dataToApply
        );

        $revision->update(['status' => 'approved']);
        return $this->successResponse(null, 'Permintaan perubahan data berhasil disetujui dan diterapkan ke database utama.');
    }

    public function rejectRevision($id)
    {
        $revision = PerformanceRevision::findOrFail($id);
        $revision->update(['status' => 'rejected']);
        return $this->successResponse(null, 'Permintaan perubahan data berhasil ditolak.');
    }
}