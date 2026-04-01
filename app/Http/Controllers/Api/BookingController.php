<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TimeSlot;
use App\Models\Booking;
use App\Jobs\ExpireBookingJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function hold(Request $request)
    {
        $request->validate([
            'time_slot_id' => 'required|string|exists:time_slots,id',
            'payment_method' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            // PESSIMISTIC LOCKING: Kunci baris slot ini di database selama transaksi berjalan.
            // Mencegah Race Condition ganda.
            $slot = TimeSlot::where('id', $request->time_slot_id)->lockForUpdate()->first();

            if ($slot->is_booked) {
                DB::rollBack();
                return $this->errorResponse('Mohon maaf, slot ini baru saja diambil orang lain.', 409);
            }

            // Kunci slot secara logika (seolah-olah sudah laku)
            $slot->update(['is_booked' => true]);

            // Buat order ID unik untuk Midtrans
            $orderId = '24JAM-' . strtoupper(Str::random(8)) . '-' . time();

            // Buat data Booking Pending (Tanpa user_id dulu, karena user belum punya akun)
            $booking = Booking::create([
                'time_slot_id' => $slot->id,
                'midtrans_order_id' => $orderId,
                'amount' => $slot->price,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'expires_at' => now()->addMinutes(15)
            ]);

            // KONFIGURASI MIDTRANS
            \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
            \Midtrans\Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
            \Midtrans\Config::$isSanitized = true;
            \Midtrans\Config::$is3ds = true;

            $params = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $slot->price,
                ],
                // Kita kosongkan customer_details karena mereka belum login
            ];

            $snapToken = \Midtrans\Snap::getSnapToken($params);

            // DISPATCH WORKER: Lempar tugas ke Job Worker untuk mengecek 15 menit dari sekarang
            ExpireBookingJob::dispatch($booking->id)->delay(now()->addMinutes(15));

            DB::commit();

            return $this->successResponse([
                'booking_id' => $booking->id,
                'snap_token' => $snapToken,
                'expires_at' => $booking->expires_at
            ], 'Slot berhasil dikunci selama 15 menit.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Terjadi kesalahan sistem: ' . $e->getMessage(), 500);
        }
    }
}