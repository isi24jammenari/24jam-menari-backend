<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Performance;
use App\Models\PerformanceRevision;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf; // Pustaka PDF

class DashboardController extends Controller
{
    // Menarik jadwal milik user yang sedang login (Beserta status Revisi)
    public function mySchedule(Request $request)
    {
        $bookings = Booking::with(['timeSlot.venue', 'performance'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'success')
            ->get();

        // Injeksi status revisi ke setiap booking agar UI Frontend bisa menyesuaikan
        $bookings->transform(function ($booking) {
            $pendingRevision = PerformanceRevision::where('booking_id', $booking->id)
                ->where('status', 'pending')
                ->first();
                
            $booking->has_pending_revision = $pendingRevision ? true : false;
            $booking->pending_revision_data = $pendingRevision ? $pendingRevision->revised_data : null;
            return $booking;
        });

        return $this->successResponse($bookings, 'Jadwal berhasil diambil.');
    }

    // Menyimpan atau Mengajukan Revisi Formulir Pementasan
    public function storePerformance(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|uuid|exists:bookings,id',
            'action'     => 'required|string|in:draft,submit', 
        ]);

        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking) {
            return $this->errorResponse('Akses ditolak. Jadwal ini bukan milik Anda.', 403);
        }

        // Cek apakah user sedang punya revisi yang belum diproses Admin
        $hasPending = PerformanceRevision::where('booking_id', $booking->id)->where('status', 'pending')->exists();
        if ($hasPending) {
            return $this->errorResponse('Anda masih memiliki pengajuan perubahan data yang sedang menunggu persetujuan Admin.', 403);
        }

        // THE DEADLINE ENGINE: 10 April 2026 23:59:59 WIB
        $deadline = \Carbon\Carbon::create(2026, 4, 10, 23, 59, 59, 'Asia/Jakarta');
        $isPastDeadline = now('Asia/Jakarta')->greaterThan($deadline);

        if ($request->action === 'submit') {
            $request->validate([
                'group_name'          => 'required|string|max:255',
                'contact_person'      => 'required|string|max:255',
                'cp_name'             => 'required|string|max:255',
                'category'            => 'required|string|in:Anak-anak,Remaja,Dewasa,Disabilitas',
                'supporters'          => 'required|string',
                'works'               => 'required|array|min:1',
                'works.*.title'       => 'required|string|max:255',
                'works.*.duration'    => 'required|numeric|min:1',
                'works.*.synopsis'    => 'required|string',
                'certificate_names'   => 'required|array|min:1',
                'certificate_names.*' => 'required|string|max:255',
                'arrival_departure'   => 'required|string',
                'music_type'          => 'required|string|in:Live,Playback',
                'instruments'         => 'nullable|array',
                'property_setting'    => 'nullable|string',
            ]);
        }

        $dataToSave = [
            'group_name'          => $request->group_name,
            'contact_person'      => $request->contact_person,
            'cp_name'             => $request->cp_name,
            'category'            => $request->category,
            'supporters'          => $request->supporters,
            'works'               => $request->works, 
            'synopsis'            => null, 
            'arrival_departure'   => $request->arrival_departure,
            'music_type'          => $request->music_type,
            'instruments'         => $request->instruments,
            'property_setting'    => $request->property_setting,
            'certificate_names'   => $request->certificate_names,
        ];

        // LOGIKA KARANTINA: Jika sudah lewat deadline DAN melakukan Submit
        if ($isPastDeadline && $request->action === 'submit') {
            PerformanceRevision::create([
                'booking_id'   => $booking->id,
                'revised_data' => $dataToSave,
                'status'       => 'pending'
            ]);
            return $this->successResponse(null, 'Batas waktu bebas edit telah lewat. Perubahan Anda telah diajukan ke Admin untuk disetujui.');
        }

        // LOGIKA NORMAL: Sebelum deadline, bebas timpa database langsung
        $dataToSave['status'] = $request->action === 'submit' ? 'completed' : 'draft';

        // LOGIKA PENOMORAN & EMAIL OTOMATIS
        $isFirstTimeFinal = false; // Detektor tembakan email
        if ($dataToSave['status'] === 'completed') {
            $existingPerf = Performance::where('booking_id', $booking->id)->first();
            if (!$existingPerf || is_null($existingPerf->invitation_number)) {
                $maxNumber = Performance::max('invitation_number');
                $dataToSave['invitation_number'] = $maxNumber >= 51 ? $maxNumber + 1 : 51;
                $isFirstTimeFinal = true; // Tandai ini adalah momen "Submit Final" pertama kalinya
            }
        }

        $performance = Performance::updateOrCreate(['booking_id' => $booking->id], $dataToSave);

        // TRIGGER PENGIRIMAN EMAIL OTOMATIS
        if ($isFirstTimeFinal) {
            try {
                // Muat ulang relasi yang dibutuhkan Blade PDF
                $bookingForPdf = Booking::with(['performance', 'timeSlot.venue', 'user'])->find($booking->id);
                \Illuminate\Support\Facades\Mail::to($bookingForPdf->user->email)->send(new \App\Mail\InvitationMail($bookingForPdf));
            } catch (\Exception $e) {
                // Fail-safe: Jika SMTP Google down, jangan hentikan aplikasi. 
                // Biarkan data tersimpan, user tetap bisa download manual di Dashboard.
                \Illuminate\Support\Facades\Log::error('Gagal kirim email undangan: ' . $e->getMessage());
            }
        }

        $message = $dataToSave['status'] === 'draft' 
            ? 'Draft berhasil disimpan sementara.' 
            : 'Data pementasan final berhasil disubmit.';

        return $this->successResponse($performance, $message);
    }

    public function publicRundown()
    {
        $slots = \App\Models\TimeSlot::with([
            'venue', 
            'booking.performance' => function ($query) {
                $query->where('status', 'completed');
            }
        ])
        ->where('is_booked', true)
        // PROTEKSI: Jangan tampilkan jadwal testing ke publik
        ->where('time_range', '!=', '00:00 - 00:01 (SHADOW)') 
        ->get();

        $safeData = $slots->map(function ($slot) {
            $performance = $slot->booking?->performance;
            return [
                'venue_name'  => $slot->venue->name ?? 'Venue Tidak Diketahui',
                'time'        => $slot->time_range,
                'group_name'  => $performance ? $performance->group_name : 'Menunggu Konfirmasi Data',
                'dance_title' => $performance ? $performance->dance_title : 'TBA',
            ];
        });

        return $this->successResponse($safeData, 'Jadwal publik berhasil ditarik.');
    }

    // ==========================================
    // FITUR DOKUMEN: Download Undangan
    // ==========================================
    public function downloadInvitation(Request $request)
    {
        $booking = Booking::with('performance')
            ->where('user_id', $request->user()->id)
            ->where('status', 'success')
            ->first();

        if (!$booking || !$booking->performance || $booking->performance->status !== 'completed') {
            return $this->errorResponse('Formulir pementasan belum lengkap atau belum disubmit final.', 400);
        }

        // Fallback jika anomali sistem menyebabkan nomor tidak ter-assign
        if (is_null($booking->performance->invitation_number)) {
            $maxNumber = Performance::max('invitation_number');
            $newNum = $maxNumber >= 51 ? $maxNumber + 1 : 51;
            $booking->performance->update(['invitation_number' => $newNum]);
            $booking->load('performance');
        }

        // Panggil PDF Facade (Pastikan use Barryvdh\DomPDF\Facade\Pdf; ada di atas, atau pakai backslash seperti di bawah)
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invitation', compact('booking'));
        
        $fileName = 'Undangan_24JamMenari_' . str_replace(' ', '_', $booking->performance->group_name) . '.pdf';
        return $pdf->download($fileName);
    }
}