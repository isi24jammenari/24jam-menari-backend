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

            // KONFIGURASI MIDTRANS CORE API
            \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
            \Midtrans\Config::$isProduction = env('MIDTRANS_IS_PRODUCTION', false);
            \Midtrans\Config::$isSanitized = true;

            $paymentType = '';
            $paymentOptions = [];

            // Mapping Metode Pembayaran Brutal ke Format Core API Midtrans
            switch ($request->payment_method) {
                case 'bca':
                case 'bni':
                case 'bri':
                    $paymentType = 'bank_transfer';
                    $paymentOptions = [
                        'bank_transfer' => [
                            'bank' => $request->payment_method
                        ]
                    ];
                    break;
                case 'mandiri':
                    $paymentType = 'echannel';
                    $paymentOptions = [
                        'echannel' => [
                            'bill_info1' => 'Payment',
                            'bill_info2' => 'Ticket 24 Jam Menari'
                        ]
                    ];
                    break;
                case 'qris':
                    $paymentType = 'qris';
                    break;
                default:
                    DB::rollBack();
                    return $this->errorResponse('Metode pembayaran tidak didukung.', 400);
            }

            $params = array_merge([
                'payment_type' => $paymentType,
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $slot->price,
                ]
            ], $paymentOptions);

            // Eksekusi charge ke Core API
            $chargeResponse = \Midtrans\CoreApi::charge($params);

            // Parsing response Core API agar frontend tidak pusing
            $paymentData = [
                'booking_id' => $booking->id,
                'expires_at' => $booking->expires_at,
                'payment_method' => $request->payment_method,
            ];

            if (in_array($request->payment_method, ['bca', 'bni', 'bri']) && isset($chargeResponse->va_numbers[0])) {
                $paymentData['va_number'] = $chargeResponse->va_numbers[0]->va_number;
            } elseif ($request->payment_method === 'mandiri') {
                $paymentData['biller_code'] = $chargeResponse->biller_code;
                $paymentData['bill_key'] = $chargeResponse->bill_key;
            } elseif ($request->payment_method === 'qris' && isset($chargeResponse->actions[0])) {
                // url gambar QR code dari Midtrans
                $paymentData['qr_code_url'] = $chargeResponse->actions[0]->url; 
            }

            // DISPATCH WORKER: Lempar tugas ke Job Worker untuk mengecek 15 menit dari sekarang
            ExpireBookingJob::dispatch($booking->id)->delay(now()->addMinutes(15));

            DB::commit();

            return $this->successResponse($paymentData, 'Slot berhasil dikunci. Silakan selesaikan pembayaran.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Terjadi kesalahan sistem: ' . $e->getMessage(), 500);
        }
    }
}