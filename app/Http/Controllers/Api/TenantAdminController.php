<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantBooking;
use App\Models\TenantStand;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantAdminController extends Controller
{
    public function index()
    {
        // Ambil semua booking sukses (manual maupun asli)
        $bookings = TenantBooking::with('stand')
            ->where('status', 'success')
            ->orderBy('updated_at', 'desc')
            ->get();

        // Ambil semua stand dan cari tahu statusnya secara mendalam
        $stands = TenantStand::orderBy('stand_number', 'asc')->get()->map(function($stand) {
            $booking = TenantBooking::where('tenant_stand_id', $stand->id)
                        ->where('status', 'success')
                        ->first();
            
            $type = 'available';
            if ($stand->is_booked) {
                if (!$booking) {
                    $type = 'manual_locked'; // Ditutup manual tanpa data
                } elseif (Str::startsWith($booking->midtrans_order_id, 'MANUAL-')) {
                    $type = 'manual_registered'; // Didaftarkan manual oleh admin
                } else {
                    $type = 'real_user_paid'; // Dibeli user asli (LOCKED)
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
        
        // PROTEKSI ABSOLUT: Cek apakah ada booking user asli yang sukses
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

    public function manualRegister(Request $request) 
    {
        $request->validate([
            'stand_id' => 'required|exists:tenant_stands,id',
            'pendaftar_name' => 'required|string',
            'phone' => 'required|string',
        ]);

        $stand = TenantStand::findOrFail($request->stand_id);
        
        // Cek apakah sudah ada user asli
        $hasRealBooking = TenantBooking::where('tenant_stand_id', $stand->id)
            ->where('status', 'success')
            ->where('midtrans_order_id', 'not like', 'MANUAL-%')
            ->exists();

        if ($hasRealBooking) {
            return $this->errorResponse('Gagal! Stand ini sudah dimiliki oleh user asli.', 403);
        }

        $stand->update(['is_booked' => true]);
        
        TenantBooking::create([
            'tenant_stand_id' => $stand->id,
            'midtrans_order_id' => 'MANUAL-' . strtoupper(Str::random(8)),
            'payment_method' => $request->payment_method ?? 'MANUAL_CASH',
            'status' => 'success',
            'pendaftar_name' => $request->pendaftar_name,
            'pendaftar_email' => $request->pendaftar_email ?? 'admin-manual@tenant.com',
            'phone' => $request->phone,
            'tenant_name' => $request->tenant_name ?? 'TENANT MANUAL',
            'product_type' => $request->product_type ?? 'MANUAL',
            'expires_at' => now()->addYears(1),
        ]);
        
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

        // TAMBAHKAN 'Kode Akses' di array $columns
        $columns = ['No', 'Timestamp Pendaftaran', 'Kode Akses', 'Email', 'No. Telepon', 'Nama Pendaftar', 'Nama Tenant', 'Kategori', 'Nomor Stand', 'Metode Bayar'];

        $callback = function() use($bookings, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
            fputcsv($file, $columns, ';'); 

            foreach ($bookings as $index => $booking) {
                fputcsv($file, [
                    $index + 1,
                    $booking->updated_at->format('Y-m-d H:i:s'),
                    $booking->access_code, // DATA KODE AKSES
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