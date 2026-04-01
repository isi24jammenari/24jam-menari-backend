<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VenueController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\DocumentController; // (Nanti di-uncomment saat controller dokumen dibuat)

// ==========================================
// PUBLIC ROUTES (Tanpa Token)
// ==========================================

// 1. Master Data (Dipanggil di Halaman Utama & Detail Venue)
Route::get('/venues', [VenueController::class, 'index']);

// 2. Authentication (Pendaftaran setelah bayar & Login)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// 3. Booking Engine (Mengunci slot 15 menit & Generate Midtrans Snap)
Route::post('/booking/hold', [BookingController::class, 'hold']);

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
    Route::get('/admin/overview', [AdminController::class, 'overview']);
    Route::get('/admin/rundown', [AdminController::class, 'rundown']);

});