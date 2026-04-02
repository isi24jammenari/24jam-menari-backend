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

            $slot = TimeSlot::where('id', $request->time_slot_id)->lockForUpdate()->first();

            if ($slot->is_booked) {
                DB::rollBack();
                return $this->errorResponse('Mohon maaf, slot ini baru saja diambil orang lain.', 409);
            }

            $slot->update(['is_booked' => true]);

            $orderId = '24JAM-' . strtoupper(Str::random(8)) . '-' . time();

            $booking = Booking::create([
                'time_slot_id'       => $slot->id,
                'midtrans_order_id'  => $orderId,
                'amount'             => $slot->price,
                'payment_method'     => $request->payment_method,
                'status'             => 'pending',
                'expires_at'         => now()->addMinutes(15)
            ]);

            // ✅ FIX 2: Pakai config() bukan env() agar tetap bekerja setelah config:cache
            \Midtrans\Config::$serverKey    = config('midtrans.server_key');
            \Midtrans\Config::$isProduction = config('midtrans.is_production');
            \Midtrans\Config::$isSanitized  = true;

            $paymentType    = '';
            $paymentOptions = [];

            switch ($request->payment_method) {
                case 'bni':
                case 'bri':
                    $paymentType    = 'bank_transfer';
                    $paymentOptions = [
                        'bank_transfer' => ['bank' => $request->payment_method]
                    ];
                    break;
                case 'mandiri':
                    $paymentType    = 'echannel';
                    $paymentOptions = [
                        'echannel' => [
                            'bill_info1' => 'Payment',
                            'bill_info2' => 'Ticket 24 Jam Menari'
                        ]
                    ];
                    break;
                case 'gopay':
                    $paymentType = 'gopay';
                    $paymentOptions = [
                        'gopay' => [
                            'enable_callback' => true,
                            'callback_url' => 'https://24jammenariisisurakarta.com/dashboard/user'
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
                'payment_type'        => $paymentType,
                'transaction_details' => [
                    'order_id'     => $orderId,
                    'gross_amount' => (int) $slot->price,
                ],
                'customer_details'    => [
                    'first_name' => 'Peserta',
                    'last_name'  => '24 Jam Menari',
                    'email'      => 'peserta@24jammenari.com',
                    'phone'      => '08111222333'
                ],
            ], $paymentOptions);

            $chargeResponse = \Midtrans\CoreApi::charge($params);

            $paymentData = [
                'booking_id'     => $booking->id,
                'expires_at'     => $booking->expires_at,
                'payment_method' => $request->payment_method,
            ];

            if (in_array($request->payment_method, ['bni', 'bri']) && isset($chargeResponse->va_numbers[0])) {
                $paymentData['va_number'] = $chargeResponse->va_numbers[0]->va_number;
            } elseif ($request->payment_method === 'mandiri') {
                $paymentData['biller_code'] = $chargeResponse->biller_code;
                $paymentData['bill_key']    = $chargeResponse->bill_key;
            } elseif ($request->payment_method === 'gopay') {
                // Tarik QR dan Deeplink dari response Midtrans
                $actions = collect($chargeResponse->actions ?? []);
                $paymentData['qr_code_url'] = $actions->firstWhere('name', 'generate-qr-code')?->url ?? null;
                $paymentData['gopay_deeplink'] = $actions->firstWhere('name', 'deeplink-redirect')?->url ?? null;
            } elseif ($request->payment_method === 'qris') {
                // ✅ FIX 1: Cari action by name, bukan by index
                // Urutan array actions dari Midtrans tidak dijamin sama setiap saat
                $generateQrAction = collect($chargeResponse->actions ?? [])
                    ->firstWhere('name', 'generate-qr-code');

                $paymentData['qr_code_url'] = $generateQrAction?->url ?? null;
            }

            ExpireBookingJob::dispatch($booking->id)->delay(now()->addMinutes(15));

            DB::commit();

            return $this->successResponse($paymentData, 'Slot berhasil dikunci. Silakan selesaikan pembayaran.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Terjadi kesalahan sistem: ' . $e->getMessage(), 500);
        }
    }

    public function status(string $bookingId)
    {
        $booking = Booking::select('id', 'status', 'expires_at', 'payment_method')
            ->where('id', $bookingId)
            ->first();

        if (!$booking) {
            return $this->errorResponse('Booking tidak ditemukan.', 404);
        }

        if ($booking->status === 'pending' && now()->isAfter($booking->expires_at)) {
            return $this->successResponse([
                'status'         => 'expired',
                'payment_method' => $booking->payment_method,
            ]);
        }

        return $this->successResponse([
            'status'         => $booking->status,
            'payment_method' => $booking->payment_method,
        ]);
    }
}

//commit
