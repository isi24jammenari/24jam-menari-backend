<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Booking;

class CheckPerformanceCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Cari apakah user ini punya jadwal (success) yang belum dilengkapi datanya (masih draft/belum ada data)
        $hasIncompleteData = Booking::where('user_id', $user->id)
            ->where('status', 'success')
            ->whereDoesntHave('performance', function ($query) {
                $query->where('status', 'completed');
            })
            ->exists();

        if ($hasIncompleteData) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Harap selesaikan Formulir Data Karya Anda (Submit Final) terlebih dahulu.',
                'error_code' => 'INCOMPLETE_PERFORMANCE'
            ], 403);
        }

        return $next($request);
    }
}