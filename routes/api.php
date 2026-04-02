<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VenueController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\DocumentController; 
use App\Http\Controllers\Api\NonstopDancerController;
use App\Http\Controllers\Api\NonstopAdminController;

// ==========================================
// PUBLIC ROUTES (Tanpa Token)
// ==========================================

// 1. Master Data (Dipanggil di Halaman Utama & Detail Venue)
Route::get('/venues', [VenueController::class, 'index']);

// 2. Authentication (Pendaftaran setelah bayar & Login)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// TAMBAHKAN INI: Route Lupa Password
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// 3. Booking Engine (Mengunci slot 15 menit & Generate Midtrans Snap)
Route::post('/booking/hold', [BookingController::class, 'hold']);

// Route untuk mengambil kembali sesi pembayaran yang nyangkut
Route::post('/booking/claim', [BookingController::class, 'claimOrphaned']);

// Route Pendaftaran 24 Jam Nonstop (File Upload Massif)
Route::post('/komunitas/register', [NonstopDancerController::class, 'register']);
Route::get('/komunitas/status', [NonstopAdminController::class, 'getStatus']);

// 4. Cek Status Booking (untuk polling frontend setelah pembayaran)
Route::get('/booking/status/{bookingId}', [BookingController::class, 'status']);

// 5. Payment Webhooks (Menerima callback/notifikasi dari server Midtrans)
Route::post('/webhooks/midtrans', [WebhookController::class, 'midtrans']);

// 6. Jadwal Publik Rundown (Dipanggil di halaman utama frontend)
Route::get('/public/rundown', [DashboardController::class, 'publicRundown']);


// ==========================================
// PROTECTED ROUTES (Wajib Bearer Token / Sanctum)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // 1. Auth Actions
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // 2. User Data / Dashboard Profile (Aman diakses meski data karya masih Draft)
    Route::get('/user', function (\Illuminate\Http\Request $request) {
        return response()->json([
            'status' => 'success', 
            'data' => $request->user()
        ]);
    });

    // 3. User Dashboard Routes Dasar (Aman diakses meski data karya masih Draft)
    Route::get('/user/schedule', [DashboardController::class, 'mySchedule']);
    Route::post('/user/performance', [DashboardController::class, 'storePerformance']);

    // ==========================================
    // GATEKEEPER ROUTES (Wajib "Submit Final")
    // ==========================================
    Route::middleware('performance.completed')->group(function () {
        
        // Endpoint Dokumen (Di-comment dulu sampai kita buat controllernya di Tahap 3)
        Route::get('/user/documents/proposal', [DocumentController::class, 'proposal']);
        Route::get('/user/documents/invitation/{bookingId}', [DocumentController::class, 'invitation']);
        Route::get('/user/documents/certificate/{bookingId}', [DocumentController::class, 'certificate']);
        
    });

    // ==========================================
    // ADMIN ROUTES
    // ==========================================
    Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
        // Overview & Mutasi
        Route::get('/overview', [AdminController::class, 'getOverview']);
        
        // Data Penari & Rundown (Digabung menjadi 1 Endpoint Masif)
        Route::get('/participants', [AdminController::class, 'getParticipants']);
        
        // Pengelolaan (Buka Tutup Akses E-Sertifikat via Cache)
        Route::post('/settings/toggle-certificate', [AdminController::class, 'toggleCertificateAccess']);
        Route::get('/settings/certificate-status', [AdminController::class, 'getCertificateStatus']);
        
        // Manajemen E-Sertifikat
        Route::get('/certificates/stats', [AdminController::class, 'getCertificateStats']);
        Route::get('/certificates/download-zip', [AdminController::class, 'generateCertificateZip']);

        // Komunitas Nonstop Routes
        Route::get('/komunitas/overview', [NonstopAdminController::class, 'getOverview']);
        Route::get('/komunitas/export', [NonstopAdminController::class, 'exportCsv']);
        Route::post('/komunitas/toggle-status', [NonstopAdminController::class, 'toggleStatus']);
    });
});