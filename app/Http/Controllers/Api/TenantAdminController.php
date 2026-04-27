<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantBooking;
use App\Models\TenantStand;
use App\Mail\TenantFormCompletedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TenantAdminController extends Controller
{
    public function index()
    {
        $bookings = TenantBooking::with('stand')
            ->where('status', 'success')
            ->orderBy('updated_at', 'desc')
            ->get();

        $stands = TenantStand::orderBy('stand_number', 'asc')->get()->map(function($stand) {
            $booking = TenantBooking::where('tenant_stand_id', $stand->id)
                        ->where('status', 'success')
                        ->first();
            
            $type = 'available';
            if ($stand->is_booked) {
                if (!$booking) {
                    $type = 'manual_locked'; 
                } elseif (Str::startsWith($booking->midtrans_order_id, 'MANUAL-')) {
                    $type = 'manual_registered'; 
                } else {
                    $type = 'real_user_paid'; 
                }
            }

            return array_merge($stand->toArray(), [
                'status_type' => $type,
                'booking_detail' => $booking
            ]);
        });

        $totalIncome = $bookings->sum(function($booking) {
            return $booking->stand ? $booking->stand->price : 1200000;
        });

        return $this->successResponse([
            'stats' => [
                'total_income' => $totalIncome,
                'total_tenants' => $bookings->count(),
                'empty_stands' => $stands->where('status_type', 'available')->count(),
            ],
            'participants' => $bookings,
            'stands' => $stands 
        ], 'Data Dashboard Admin Tenant');
    }

    public function toggleStandStatus($id) 
    {
        $stand = TenantStand::findOrFail($id);
        
        $hasRealBooking = TenantBooking::where('tenant_stand_id', $id)
            ->where('status', 'success')
            ->where('midtrans_order_id', 'not like', 'MANUAL-%')
            ->exists();

        if ($hasRealBooking) {
            return $this->errorResponse('Aksi Ditolak! Stand ini telah dibayar oleh user asli dan tidak dapat dibuka kembali secara manual.', 403);
        }

        $stand->update(['is_booked' => !$stand->is_booked]);
        return $this->successResponse(null, 'Status stand berhasil diperbarui.');
    }

    public function toggleAllStands(Request $request) 
    {
        $isBooked = $request->action === 'lock';
        TenantStand::query()->update(['is_booked' => $isBooked]);
        
        return $this->successResponse(null, 'Seluruh stand berhasil ' . ($isBooked ? 'ditutup.' : 'dibuka.'));
    }

    public function manualRegister(Request $request) 
    {
        $request->validate([
            'stand_id' => 'required|exists:tenant_stands,id',
            'pendaftar_name' => 'required|string',
            'phone' => 'required|string',
        ]);

        $stand = TenantStand::findOrFail($request->stand_id);
        
        $hasRealBooking = TenantBooking::where('tenant_stand_id', $stand->id)
            ->where('status', 'success')
            ->where('midtrans_order_id', 'not like', 'MANUAL-%')
            ->exists();

        if ($hasRealBooking) {
            return $this->errorResponse('Gagal! Stand ini sudah dimiliki oleh user asli.', 403);
        }

        $stand->update(['is_booked' => true]);
        
        // PERBAIKAN: Menyertakan 'amount' agar tidak terjadi error SQL Not Null
        $booking = TenantBooking::create([
            'tenant_stand_id' => $stand->id,
            'midtrans_order_id' => 'MANUAL-' . strtoupper(Str::random(8)),
            'amount' => $stand->price, 
            'payment_method' => $request->payment_method ?? 'MANUAL_CASH',
            'status' => 'success',
            'pendaftar_name' => $request->pendaftar_name,
            'pendaftar_email' => $request->pendaftar_email ?? 'admin-manual@tenant.com',
            'phone' => $request->phone,
            'tenant_name' => $request->tenant_name ?? 'TENANT MANUAL',
            'product_type' => $request->product_type ?? 'MANUAL',
            'expires_at' => now()->addYears(1),
        ]);
        
        // PERBAIKAN: Kirim email otomatis jika alamat email disertakan
        if ($booking->pendaftar_email && $booking->pendaftar_email !== 'admin-manual@tenant.com') {
            try {
                Mail::to($booking->pendaftar_email)->queue(new TenantFormCompletedMail($booking));
            } catch (\Exception $e) {
                // Mengabaikan error koneksi SMTP agar stand tetap berhasil tersimpan
            }
        }
        
        return $this->successResponse(null, 'Pendaftaran manual berhasil disimpan!');
    }

    public function exportCsv()
    {
        $bookings = TenantBooking::with('stand')
            ->where('status', 'success')
            ->orderBy('updated_at', 'asc')
            ->get();

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=Data-Tenant-Bazaar-2026.csv",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['No', 'Timestamp Pendaftaran', 'Kode Akses', 'Email', 'No. Telepon', 'Nama Pendaftar', 'Nama Tenant', 'Kategori', 'Nomor Stand', 'Metode Bayar'];

        $callback = function() use($bookings, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); 
            fputcsv($file, $columns, ';'); 

            foreach ($bookings as $index => $booking) {
                fputcsv($file, [
                    $index + 1,
                    $booking->updated_at->format('Y-m-d H:i:s'),
                    $booking->access_code,
                    $booking->pendaftar_email,
                    $booking->phone,
                    $booking->pendaftar_name,
                    $booking->tenant_name ?? '-',
                    $booking->product_type ?? '-',
                    $booking->stand ? $booking->stand->stand_number : '-',
                    strtoupper($booking->payment_method ?? '-')
                ], ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}