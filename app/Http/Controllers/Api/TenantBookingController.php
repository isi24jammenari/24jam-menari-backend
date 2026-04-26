<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantStand;
use App\Models\TenantBooking;
use App\Jobs\ExpireTenantBookingJob;
use App\Mail\TenantFormCompletedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class TenantBookingController extends Controller
{
    public function getStands()
    {
        $stands = TenantStand::orderBy('stand_number', 'asc')->get();
        return $this->successResponse($stands, 'Data stand tenant');
    }

    public function hold(Request $request)
    {
        $request->validate([
            'stand_id' => 'required|string|exists:tenant_stands,id',
            'payment_method' => 'required|string',
            'pendaftar_name' => 'required|string|max:255',
            'pendaftar_email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
        ]);

        try {
            DB::beginTransaction();

            $stand = TenantStand::where('id', $request->stand_id)->lockForUpdate()->first();

            if ($stand->is_booked) {
                DB::rollBack();
                return $this->errorResponse('Stand ini sudah dibooking.', 409);
            }

            $stand->update(['is_booked' => true]);

            $orderId = '24JAM-TNT-' . strtoupper(Str::random(6)) . '-' . time();

            // --- PERHITUNGAN BIAYA ADMIN DINAMIS ---
            $basePrice = $stand->price; // Mengambil harga asli dari database
            $payoutFee = 4000;          // Biaya tarik dana
            $adminFee = 0;              // Biaya gerbang pembayaran

            switch ($request->payment_method) {
                case 'gopay':
                    $adminFee = ceil($basePrice * 0.02) + $payoutFee; // 2% + 4000
                    break;
                case 'qris':
                    $adminFee = ceil($basePrice * 0.007) + $payoutFee; // 0.7% + 4000
                    break;
                default: // Virtual Account (BNI, BRI, Mandiri)
                    $adminFee = 4000 + $payoutFee; // 4000 + 4000
                    break;
            }

            $totalAmount = $basePrice + $adminFee;

            $booking = TenantBooking::create([
                'tenant_stand_id'   => $stand->id,
                'midtrans_order_id' => $orderId,
                'amount'            => $totalAmount, // Menyimpan total keseluruhan (termasuk admin)
                'payment_method'    => $request->payment_method,
                'status'            => 'pending',
                'expires_at'        => now()->addMinutes(15),
                'pendaftar_name'    => $request->pendaftar_name,
                'pendaftar_email'   => $request->pendaftar_email,
                'phone'             => $request->phone,
            ]);

            \Midtrans\Config::$serverKey    = config('midtrans.server_key');
            \Midtrans\Config::$isProduction = config('midtrans.is_production');
            \Midtrans\Config::$isSanitized  = true;

            $paymentType = '';
            $paymentOptions = [];

            switch ($request->payment_method) {
                case 'bni':
                case 'bri':
                    $paymentType = 'bank_transfer';
                    $paymentOptions = ['bank_transfer' => ['bank' => $request->payment_method]];
                    break;
                case 'mandiri':
                    $paymentType = 'echannel';
                    $paymentOptions = ['echannel' => ['bill_info1' => 'Payment', 'bill_info2' => 'Tenant 24 Jam Menari']];
                    break;
                case 'gopay':
                    $paymentType = 'gopay';
                    $paymentOptions = ['gopay' => ['enable_callback' => true, 'callback_url' => 'https://tenant.24jammenariisisurakarta.com/form?order_id='.$orderId]];
                    break;
                case 'qris':
                    $paymentType = 'qris';
                    break;
                default:
                    DB::rollBack();
                    return $this->errorResponse('Metode pembayaran tidak valid.', 400);
            }

            $params = array_merge([
                'payment_type' => $paymentType,
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int) $totalAmount,
                ],
                // PENAMBAHAN ITEM DETAILS AGAR MUNCUL DI RESI MIDTRANS
                'item_details' => [
                    [
                        'id' => $stand->id,
                        'price' => (int) $basePrice,
                        'quantity' => 1,
                        'name' => "Stand Bazaar #" . $stand->stand_number,
                    ],
                    [
                        'id' => 'ADMIN-FEE',
                        'price' => (int) $adminFee,
                        'quantity' => 1,
                        'name' => "Biaya Layanan & Penarikan Dana",
                    ]
                ],
                'customer_details' => [
                    'first_name' => $request->pendaftar_name,
                    'email'      => $request->pendaftar_email,
                    'phone'      => $request->phone
                ],
            ], $paymentOptions);

            $chargeResponse = \Midtrans\CoreApi::charge($params);

            $paymentData = [
                'order_id'       => $orderId,
                'expires_at'     => $booking->expires_at,
                'payment_method' => $request->payment_method,
            ];

            if (in_array($request->payment_method, ['bni', 'bri']) && isset($chargeResponse->va_numbers[0])) {
                $paymentData['va_number'] = $chargeResponse->va_numbers[0]->va_number;
            } elseif ($request->payment_method === 'mandiri') {
                $paymentData['biller_code'] = $chargeResponse->biller_code;
                $paymentData['bill_key']    = $chargeResponse->bill_key;
            } elseif ($request->payment_method === 'gopay') {
                $actions = collect($chargeResponse->actions ?? []);
                $paymentData['qr_code_url'] = $actions->firstWhere('name', 'generate-qr-code')?->url ?? null;
                $paymentData['gopay_deeplink'] = $actions->firstWhere('name', 'deeplink-redirect')?->url ?? null;
            } elseif ($request->payment_method === 'qris') {
                $generateQrAction = collect($chargeResponse->actions ?? [])->firstWhere('name', 'generate-qr-code');
                $paymentData['qr_code_url'] = $generateQrAction?->url ?? null;
            }

            ExpireTenantBookingJob::dispatch($booking->id)->delay(now()->addMinutes(15));

            DB::commit();
            return $this->successResponse($paymentData, 'Stand dikunci. Selesaikan pembayaran.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Terjadi kesalahan: ' . $e->getMessage(), 500);
        }
    }

    public function status(string $orderId)
    {
        $booking = TenantBooking::with('stand')
            ->where('midtrans_order_id', $orderId)
            ->orWhere('access_code', $orderId)
            ->first();

        if (!$booking) {
            return $this->errorResponse('Order tidak ditemukan.', 404);
        }

        return $this->successResponse([
            'status'         => $booking->status,
            'access_code'    => $booking->access_code,
            'pendaftar_name' => $booking->pendaftar_name,
            'tenant_name'    => $booking->tenant_name,
            'product_type'   => $booking->product_type,
            'stand_number'   => $booking->stand ? $booking->stand->stand_number : null,
        ]);
    }

    public function submitForm(Request $request)
    {
        $request->validate([
            'order_id' => 'nullable|string',
            'access_code' => 'nullable|string',
            'tenant_name' => 'required|string|max:255',
            'product_type' => 'required|string|max:255',
        ]);

        if (!$request->order_id && !$request->access_code) {
            return $this->errorResponse('Order ID atau Access Code wajib disertakan.', 400);
        }

        $query = TenantBooking::where('status', 'success');
        if ($request->order_id) {
            $query->where('midtrans_order_id', $request->order_id);
        } else {
            $query->where('access_code', $request->access_code);
        }

        $booking = $query->first();

        if (!$booking) {
            return $this->errorResponse('Data booking tidak valid atau belum lunas.', 404);
        }

        $booking->update([
            'tenant_name' => $request->tenant_name,
            'product_type' => $request->product_type,
        ]);

        Mail::to($booking->pendaftar_email)->queue(new TenantFormCompletedMail($booking));

        return $this->successResponse(null, 'Formulir berhasil disimpan!');
    }
}