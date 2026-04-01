<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    // 1. Download File Statis (Proposal)
    public function proposal()
    {
        // Pastikan Anda menaruh file "proposal_24jammenari.pdf" di dalam folder "storage/app/public/"
        $filePath = 'public/proposal_24jammenari.pdf';

        if (!Storage::exists($filePath)) {
            return response()->json(['message' => 'File proposal belum diunggah oleh panitia.'], 404);
        }

        return Storage::download($filePath, 'Proposal_24_Jam_Menari.pdf');
    }

    // 2. Download File Dinamis (Undangan)
    public function invitation(Request $request, $bookingId)
    {
        // Validasi ekstra: pastikan booking milik user dan datanya sudah komplit
        $booking = Booking::with(['user', 'performance', 'timeSlot.venue'])
            ->where('id', $bookingId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking || $booking->performance->status !== 'completed') {
            return response()->json(['message' => 'Data tidak valid atau Formulir Karya belum disubmit final.'], 403);
        }

        // Generate PDF dari view blade 'resources/views/pdf/invitation.blade.php'
        $pdf = Pdf::loadView('pdf.invitation', [
            'booking' => $booking
        ]);

        // Opsional: atur ukuran kertas
        $pdf->setPaper('A4', 'portrait');

        return $pdf->download('Undangan_Pementasan_' . str_replace(' ', '_', $booking->performance->group_name) . '.pdf');
    }

    // 3. Download File Dinamis (E-Sertifikat)
    public function certificate(Request $request, $bookingId)
    {
        $booking = Booking::with(['user', 'performance'])
            ->where('id', $bookingId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$booking || $booking->performance->status !== 'completed') {
            return response()->json(['message' => 'Data tidak valid.'], 403);
        }

        // Generate PDF Sertifikat (Landscape)
        $pdf = Pdf::loadView('pdf.certificate', [
            'booking' => $booking
        ]);

        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('E_Sertifikat_' . str_replace(' ', '_', $booking->performance->group_name) . '.pdf');
    }
}