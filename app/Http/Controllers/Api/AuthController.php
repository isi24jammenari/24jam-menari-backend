<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // 1. Validasi Input — booking_id wajib ada dan ada di tabel bookings
        $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|string|email|max:255|unique:users',
            'password'              => 'required|string|min:8|confirmed',
            'booking_id'            => 'required|uuid|exists:bookings,id',
        ]);

        // 2. Cek status booking SECARA EKSPLISIT sebelum membuat user
        //    Ini menggantikan silent fail sebelumnya.
        $booking = Booking::where('id', $request->booking_id)
            ->whereNull('user_id') // Pastikan belum diklaim akun lain
            ->first();

        if (!$booking) {
            return $this->errorResponse(
                'Booking tidak ditemukan atau sudah terikat ke akun lain.',
                422
            );
        }

        // ✅ FIX 6: Cek status secara eksplisit — berikan pesan error yang jelas ke frontend
        if ($booking->status !== 'success') {
            $statusMessages = [
                'pending'  => 'Pembayaran Anda belum terkonfirmasi. Harap tunggu beberapa saat lalu coba lagi.',
                'expired'  => 'Sesi pembayaran telah kedaluwarsa. Silakan ulangi proses pemesanan dari awal.',
                'failed'   => 'Pembayaran gagal. Silakan ulangi proses pemesanan dari awal.',
            ];

            $message = $statusMessages[$booking->status]
                ?? 'Status pembayaran tidak valid. Silakan hubungi panitia.';

            return $this->errorResponse($message, 422);
        }

        // 3. Buat User & Ikat Booking dalam satu transaksi DB
        //    Jika salah satu gagal, keduanya di-rollback.
        DB::beginTransaction();
        try {
            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'role'     => 'user',
            ]);

            // ✅ FIX 6: Gunakan affected rows untuk deteksi race condition
            $affected = Booking::where('id', $request->booking_id)
                ->where('status', 'success')
                ->whereNull('user_id')
                ->update(['user_id' => $user->id]);

            // Jika 0 rows terupdate, ada race condition — rollback dan beri error
            if ($affected === 0) {
                DB::rollBack();
                return $this->errorResponse(
                    'Gagal mengikat booking ke akun. Booking mungkin sudah diklaim. Silakan hubungi panitia.',
                    409
                );
            }

            // 4. Generate Token Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return $this->successResponse([
                'user'         => $user,
                'access_token' => $token,
                'token_type'   => 'Bearer',
            ], 'Registrasi berhasil. Jadwal telah diikat ke akun Anda.', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Terjadi kesalahan sistem: ' . $e->getMessage(), 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Email atau password salah.', 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user'         => $user,
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ], 'Login berhasil.');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse(null, 'Logout berhasil.');
    }
}
