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
        $serverKey = env('MIDTRANS_SERVER_KEY');
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        // Validasi Signature (Keamanan Murni)
        if ($hashed !== $request->signature_key) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $booking = Booking::where('midtrans_order_id', $request->order_id)->first();
        if (!$booking) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $transactionStatus = $request->transaction_status;

        // IDEMPOTENCY: Jika sudah sukses/expired, abaikan webhook susulan
        if ($booking->status === 'success' || $booking->status === 'expired') {
            return response()->json(['message' => 'Already processed']);
        }

        if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
            $booking->update(['status' => 'success']);
            // Slot biarkan is_booked = true (kunci permanen)
            Log::info("Payment Success for Order: {$request->order_id}");
        } 
        else if ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
            $booking->update(['status' => 'failed']);
            // Bebaskan slot karena gagal bayar
            $booking->timeSlot->update(['is_booked' => false]);
            Log::info("Payment Failed/Expired for Order: {$request->order_id}");
        }

        return response()->json(['message' => 'Webhook received']);
    }
}