<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function midtrans(Request $request)
    {
        // ✅ FIX 1: Pakai config() bukan env() agar tidak null setelah config:cache
        $serverKey = config('midtrans.server_key');

        $hashed = hash(
            "sha512",
            $request->order_id .
            $request->status_code .
            $request->gross_amount .
            $serverKey
        );

        // Validasi Signature
        if ($hashed !== $request->signature_key) {
            Log::warning("Midtrans webhook invalid signature for order: {$request->order_id}");
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        // CEK APAKAH INI TRANSAKSI TENANT ATAU PENARI
        if (\Illuminate\Support\Str::startsWith($request->order_id, '24JAM-TNT-')) {
            $booking = \App\Models\TenantBooking::where('midtrans_order_id', $request->order_id)->first();
            if (!$booking) return response()->json(['message' => 'Tenant Order not found'], 404);
            
            $transactionStatus = $request->transaction_status;
            
            if ($booking->status === 'success' || $booking->status === 'expired') {
                return response()->json(['message' => 'Already processed']);
            }

            if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
                $accessCode = 'TNT-' . strtoupper(\Illuminate\Support\Str::random(6));
                $booking->update([
                    'status' => 'success',
                    'access_code' => $accessCode
                ]);
                Log::info("Tenant Payment Success: {$request->order_id}");
                
                // Kirim Email 1 (Kode Akses)
                \Illuminate\Support\Facades\Mail::to($booking->pendaftar_email)
                    ->queue(new \App\Mail\TenantPaymentSuccessMail($booking));

            } elseif (in_array($transactionStatus, ['cancel', 'deny', 'expire'])) {
                $booking->update(['status' => 'failed']);
                if ($booking->stand) {
                    $booking->stand->update(['is_booked' => false]);
                }
                Log::info("Tenant Payment Failed: {$request->order_id}");
            }
            return response()->json(['message' => 'Tenant Webhook processed']);
        } 
        
        // JIKA BUKAN TENANT, MASUK KE LOGIKA PENARI EKSISTING
        $booking = Booking::where('midtrans_order_id', $request->order_id)->first();
        if (!$booking) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $transactionStatus = $request->transaction_status;

        if ($booking->status === 'success' || $booking->status === 'expired') {
            return response()->json(['message' => 'Already processed']);
        }

        if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
            $booking->update(['status' => 'success']);
            Log::info("Payment Success for Order: {$request->order_id}");
        } elseif (
            $transactionStatus === 'cancel' ||
            $transactionStatus === 'deny'  ||
            $transactionStatus === 'expire'
        ) {
            $booking->update(['status' => 'failed']);
            $booking->timeSlot()->update(['is_booked' => false]);
            Log::info("Payment Failed/Expired for Order: {$request->order_id}");
        }

        return response()->json(['message' => 'Webhook received']);
    }
}
