<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TenantBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function midtrans(Request $request)
    {
        // ✅ FIX: Gunakan config() agar nilai tidak null jika server menggunakan php artisan config:cache
        $serverKey = config('midtrans.server_key');

        $hashed = hash(
            "sha512",
            $request->order_id .
            $request->status_code .
            $request->gross_amount .
            $serverKey
        );

        // 1. Verifikasi Keamanan (Signature Key)
        if ($hashed !== $request->signature_key) {
            Log::warning("Midtrans webhook invalid signature for order: {$request->order_id}");
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $transactionStatus = $request->transaction_status;

        // =======================================================
        // CABANG 1: DETEKSI APAKAH INI TRANSAKSI TENANT BAZAAR
        // =======================================================
        $tenantBooking = TenantBooking::where('midtrans_order_id', $request->order_id)->first();
        
        if ($tenantBooking) {
            // Cegah proses berulang jika Midtrans mengirim Webhook ganda
            if ($tenantBooking->status === 'success' || $tenantBooking->status === 'expired') {
                return response()->json(['message' => 'Tenant Order Already processed']);
            }

            if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
                // Generate Kode Akses Acak untuk Tenant (Misal: TNT-A1B2C3)
                $accessCode = 'TNT-' . strtoupper(Str::random(6));
                
                $tenantBooking->update([
                    'status' => 'success',
                    'access_code' => $accessCode
                ]);
                
                Log::info("Tenant Payment Success: {$request->order_id}");
                
                // Kirim Email Berisi Kode Akses (Gunakan try-catch agar webhook tidak timeout jika email gagal)
                try {
                    Mail::to($tenantBooking->pendaftar_email)->queue(new \App\Mail\TenantPaymentSuccessMail($tenantBooking));
                } catch (\Exception $e) {
                    Log::error("Email Tenant Success gagal dikirim: " . $e->getMessage());
                }

            } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
                $tenantBooking->update(['status' => 'failed']);
                
                // Bebaskan kembali stand yang digembok
                if ($tenantBooking->stand) {
                    $tenantBooking->stand->update(['is_booked' => false]);
                }
                Log::info("Tenant Payment Failed/Expired: {$request->order_id}");
            }
            
            return response()->json(['message' => 'Tenant Webhook processed']);
        } 
        
        // =======================================================
        // CABANG 2: JIKA BUKAN TENANT, PROSES SEBAGAI PENARI (LOGIKA EKSISTING ANDA)
        // =======================================================
        $booking = Booking::where('midtrans_order_id', $request->order_id)->first();
        
        if (!$booking) {
            Log::warning("Order not found in any table: {$request->order_id}");
            return response()->json(['message' => 'Order not found in any table'], 404);
        }

        if ($booking->status === 'success' || $booking->status === 'expired') {
            return response()->json(['message' => 'Penari Order Already processed']);
        }

        if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
            $booking->update(['status' => 'success']);
            Log::info("Payment Success for Order (Penari): {$request->order_id}");
            
        } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
            $booking->update(['status' => 'failed']);
            
            // Bebaskan time slot penari
            if ($booking->timeSlot) {
                $booking->timeSlot->update(['is_booked' => false]);
            }
            Log::info("Payment Failed/Expired for Order (Penari): {$request->order_id}");
        }

        return response()->json(['message' => 'Webhook received']);
    }
}