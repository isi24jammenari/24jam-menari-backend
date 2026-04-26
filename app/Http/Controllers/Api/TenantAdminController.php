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
        $bookings = TenantBooking::with('stand')
            ->where('status', 'success')
            ->orderBy('updated_at', 'desc')
            ->get();

        $stands = TenantStand::orderBy('stand_number', 'asc')->get();

        $stats = [
            'total_income' => $bookings->sum('amount'),
            'total_tenants' => $bookings->count(),
            'empty_stands' => $stands->where('is_booked', false)->count(),
        ];

        return response()->json([
            'status' => 'success',
            'stats' => $stats,
            'participants' => $bookings,
            'stands' => $stands // Dikirim agar Frontend bisa merender Grid Manajemen
        ]);
    }

    public function toggleStandStatus($id) 
    {
        $stand = TenantStand::findOrFail($id);
        $stand->update(['is_booked' => !$stand->is_booked]);
        return response()->json(['status' => 'success', 'message' => 'Status stand berhasil diperbarui.']);
    }

    public function toggleAllStands(Request $request) 
    {
        $isBooked = $request->action === 'lock';
        TenantStand::query()->update(['is_booked' => $isBooked]);
        return response()->json(['status' => 'success', 'message' => 'Seluruh stand berhasil ' . ($isBooked ? 'ditutup.' : 'dibuka.')]);
    }

    public function manualRegister(Request $request) 
    {
        $request->validate([
            'stand_id' => 'required|exists:tenant_stands,id',
            'pendaftar_name' => 'required|string',
            'phone' => 'required|string',
        ]);

        $stand = TenantStand::findOrFail($request->stand_id);
        if ($stand->is_booked) {
            return response()->json(['message' => 'Stand ini sudah berstatus terkunci/disewa.'], 400);
        }

        $stand->update(['is_booked' => true]);
        
        TenantBooking::create([
            'tenant_stand_id' => $stand->id,
            'midtrans_order_id' => 'MANUAL-' . strtoupper(Str::random(8)),
            'amount' => $stand->price,
            'payment_method' => $request->payment_method ?? 'MANUAL_CASH',
            'status' => 'success', // Langsung lunas
            'pendaftar_name' => $request->pendaftar_name,
            'pendaftar_email' => $request->pendaftar_email ?? 'admin-manual@tenant.com',
            'phone' => $request->phone,
            'tenant_name' => $request->tenant_name ?? 'TENANT MANUAL',
            'product_type' => $request->product_type ?? 'MANUAL',
            'expires_at' => now()->addYears(1),
        ]);
        
        return response()->json(['status' => 'success', 'message' => 'Pendaftaran manual berhasil disimpan!']);
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

        $columns = ['No', 'Timestamp Pendaftaran', 'Email', 'No. Telepon', 'Nama Pendaftar', 'Nama Tenant', 'Kategori', 'Nomor Stand', 'Metode Bayar'];

        $callback = function() use($bookings, $columns) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM untuk Excel
            fputcsv($file, $columns, ';'); // Delimiter pemisah khusus Excel

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