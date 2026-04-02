<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NonstopDancer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NonstopAdminController extends Controller
{
    // Mengambil status buka/tutup form saat ini (Default: true / Buka)
    public function getStatus()
    {
        $isOpen = Cache::get('nonstop_registration_open', true);
        return response()->json(['status' => 'success', 'data' => ['is_open' => $isOpen]]);
    }

    // Toggle Buka / Tutup Form Pendaftaran
    public function toggleStatus(Request $request)
    {
        $request->validate(['is_open' => 'required|boolean']);
        Cache::forever('nonstop_registration_open', $request->is_open);
        
        $state = $request->is_open ? 'dibuka' : 'ditutup';
        return $this->successResponse(null, "Formulir pendaftaran Penari Nonstop berhasil $state.");
    }

    // Mengambil data pendaftar untuk Dashboard Admin
    public function getOverview()
    {
        $totalDancers = NonstopDancer::count();
        $dancers = NonstopDancer::orderBy('created_at', 'desc')->paginate(20);

        return $this->successResponse([
            'stats' => ['total_pendaftar' => $totalDancers],
            'dancers' => $dancers
        ], 'Berhasil mengambil data Penari Nonstop.');
    }

    // Export Data ke CSV (Standar Industri, Cepat, Tanpa Plugin Berat)
    public function exportCsv()
    {
        $dancers = NonstopDancer::orderBy('created_at', 'asc')->get();

        return response()->streamDownload(function () use ($dancers) {
            $file = fopen('php://output', 'w');
            // Header Tabel
            fputcsv($file, ['ID', 'Nama', 'Email', 'No. HP', 'Karya Masterpiece', 'Pendamping', 'Waktu Daftar', 'File Surat Sehat (G-Drive ID)', 'File CV', 'File Foto', 'File Video']);

            foreach ($dancers as $dancer) {
                fputcsv($file, [
                    $dancer->id,
                    $dancer->name,
                    $dancer->email,
                    $dancer->phone,
                    $dancer->masterpiece_title,
                    $dancer->companions_identity,
                    $dancer->created_at->format('Y-m-d H:i:s'),
                    $dancer->health_cert_file_id,
                    $dancer->cv_file_id,
                    $dancer->photo_file_id,
                    $dancer->video_file_id,
                ]);
            }
            fclose($file);
        }, 'Data_Pendaftar_Penari_Nonstop_2026.csv');
    }
}