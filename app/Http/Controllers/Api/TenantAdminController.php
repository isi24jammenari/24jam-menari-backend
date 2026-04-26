<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantBooking;
use App\Models\TenantStand;
use Illuminate\Http\Request;

class TenantAdminController extends Controller
{
    public function index()
    {
        $stats = [
            'total_income' => TenantBooking::where('status', 'success')->sum('amount'),
            'total_tenants' => TenantBooking::where('status', 'success')->count(),
            'empty_stands' => TenantStand::where('is_booked', false)->count(),
        ];

        $participants = TenantBooking::with('stand')
            ->where('status', 'success')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse([
            'stats' => $stats,
            'participants' => $participants
        ], 'Data pendaftar tenant berhasil diambil.');
    }

    public function exportCsv()
    {
        $bookings = \App\Models\TenantBooking::with('stand')
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

        // Sesuai urutan yang diminta + Stand & Metode
        $columns = ['No', 'Timestamp Pendaftaran', 'Email', 'No. Telepon', 'Nama Pendaftar', 'Nama Tenant', 'Kategori', 'Nomor Stand', 'Metode Bayar'];

        $callback = function() use($bookings, $columns) {
            $file = fopen('php://output', 'w');
            
            // 1. TAMBAHKAN BOM (Byte Order Mark) agar Excel membaca karakter UTF-8 dengan sempurna
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // 2. GUNAKAN DELIMITER TITIK KOMA (;) agar langsung dibaca sebagai tabel oleh Excel Indonesia
            fputcsv($file, $columns, ';');

            foreach ($bookings as $index => $booking) {
                fputcsv($file, [
                    $index + 1,
                    $booking->updated_at->format('Y-m-d H:i:s'),
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