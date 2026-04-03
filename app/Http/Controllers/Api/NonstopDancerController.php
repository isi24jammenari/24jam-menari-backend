<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NonstopDancer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Mail\NonstopRegistrationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class NonstopDancerController extends Controller
{
    public function register(Request $request)
    {
        $isOpen = Cache::get('nonstop_registration_open', true);
        if (!$isOpen) {
            return $this->errorResponse('Pendaftaran Penari 24 Jam Non-Stop saat ini sedang ditutup.', 403);
        }

        $request->validate([
            'name'                => 'required|string|max:255',
            'email'               => 'required|email|unique:nonstop_dancers,email',
            'phone'               => 'required|string|max:20',
            'masterpiece_title'   => 'required|string|max:255',
            'companions_identity' => 'required|string',
            
            'health_cert' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', 
            'cv'          => 'required|file|mimes:pdf|max:5120', 
            'photo'       => 'required|file|mimes:jpg,jpeg,png|max:5120', 
            'video'       => 'required|file|mimes:mp4,mov,avi|max:25600', 
        ], [
            'email.unique' => 'Email ini sudah terdaftar sebagai Penari Nonstop.',
            'video.max'    => 'Ukuran video maksimal adalah 25MB. Silakan kompres video Anda.',
        ]);

        DB::beginTransaction();
        try {
            $nameSlug = str_replace(' ', '_', strtolower($request->name));
            $timestamp = now()->format('Ymd_His');
            $folderPrefix = "{$nameSlug}_{$timestamp}";

            // PROSES UPLOAD KE GDRIVE
            $healthCertPath = $request->file('health_cert')->storeAs($folderPrefix, '1_SuratSehat.' . $request->file('health_cert')->extension(), 'google');
            $cvPath         = $request->file('cv')->storeAs($folderPrefix, '2_CV.' . $request->file('cv')->extension(), 'google');
            $photoPath      = $request->file('photo')->storeAs($folderPrefix, '3_Foto.' . $request->file('photo')->extension(), 'google');
            $videoPath      = $request->file('video')->storeAs($folderPrefix, '4_VideoMotivasi.' . $request->file('video')->extension(), 'google');

            // PROTEKSI SILENT FAILURE: Jika GDrive menolak, gagalkan sistem!
            if (!$healthCertPath || !$cvPath || !$photoPath || !$videoPath) {
                throw new \Exception("Akses ditolak oleh Google Drive. Pastikan Service Account sudah dijadikan 'Editor' di folder tersebut, dan Folder ID sudah benar.");
            }

            $dancer = NonstopDancer::create([
                'name'                => $request->name,
                'email'               => $request->email,
                'phone'               => $request->phone,
                'masterpiece_title'   => $request->masterpiece_title,
                'companions_identity' => $request->companions_identity,
                'health_cert_file_id' => $healthCertPath,
                'cv_file_id'          => $cvPath,
                'photo_file_id'       => $photoPath,
                'video_file_id'       => $videoPath,
            ]);

            DB::commit();

            try {
                Mail::to($dancer->email)->send(new NonstopRegistrationMail($dancer));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Gagal kirim email nonstop: ' . $e->getMessage());
            }

            return $this->successResponse(
                $dancer, 
                'Pendaftaran Penari 24 Jam Nonstop berhasil disubmit! Silakan cek email Anda untuk detail konfirmasi.', 
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            // Akan memunculkan error asli dari Google ke layar Frontend
            return $this->errorResponse('Gagal mengunggah file: ' . $e->getMessage(), 500);
        }
    }
}