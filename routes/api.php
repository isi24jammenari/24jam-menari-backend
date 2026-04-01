<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VenueController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AdminController;

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

// 4. Payment Webhooks (Menerima callback/notifikasi dari server Midtrans)
Route::post('/webhooks/midtrans', [WebhookController::class, 'midtrans']);


// ==========================================
// PROTECTED ROUTES (Wajib Bearer Token / Sanctum)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // 1. Auth Actions
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // 2. User Data / Dashboard Profile
    Route::get('/user', function (\Illuminate\Http\Request $request) {
        return response()->json([
            'status' => 'success', 
            'data' => $request->user()
        ]);
    });

    // User Dashboard Routes
    Route::get('/user/schedule', [DashboardController::class, 'mySchedule']);
    Route::post('/user/performance', [DashboardController::class, 'storePerformance']);

    // Admin Dashboard Routes
    Route::get('/admin/overview', [AdminController::class, 'overview']);
    Route::get('/admin/rundown', [AdminController::class, 'rundown']);

});