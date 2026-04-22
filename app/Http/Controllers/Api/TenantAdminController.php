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
        $bookings = TenantBooking::with('stand')->where('status', 'success')->get();
        $filename = "data-tenant-" . date('Y-m-d') . ".csv";

        $handle = fopen('php://output', 'w');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        // Header CSV
        fputcsv($handle, ['Stand', 'Nama Pendaftar', 'Email', 'No. WA', 'Nama Tenant', 'Jenis Produk', 'Status Bayar']);

        foreach ($bookings as $b) {
            fputcsv($handle, [
                $b->stand->stand_number,
                $b->pendaftar_name,
                $b->pendaftar_email,
                $b->phone,
                $b->tenant_name ?? '-',
                $b->product_type ?? '-',
                $b->status
            ]);
        }

        fclose($handle);
        exit;
    }
}