<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
                'name'             => $request->name,
                'email'            => $request->email,
                'password'         => Hash::make($request->password),
                'role'             => 'user',
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

            // 5. Dispatch Email ke Redis Queue (Asinkron)
            try {
                // Tarik relasi yang dibutuhkan blade email
                $booking->load(['timeSlot.venue']);
                \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\PaymentSuccessMail($booking, $user));
            } catch (\Exception $e) {
                // Hanya log error agar API tidak meledak ke user jika Resend down
                \Illuminate\Support\Facades\Log::error('Gagal queue email registrasi: ' . $e->getMessage());
            }

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

        // ==================================================
        // GATEKEEPER: STRICT ROLE SEPARATION BERDASARKAN URL
        // ==================================================
        $origin = (string) $request->header('origin', '');
        $referer = (string) $request->header('referer', '');

        // Deteksi apakah request berasal dari subdomain 'admin.'
        $isAdminDomain = str_contains($origin, 'admin.') || str_contains($referer, 'admin.');

        // 1. Jika URL-nya Admin, tapi role-nya BUKAN admin (User biasa nyasar ke portal Admin)
        if ($isAdminDomain && $user->role !== 'admin') {
            return $this->errorResponse('Akses ditolak! Halaman ini khusus untuk Admin. Silakan login di portal utama.', 403);
        }

        // 2. Jika URL-nya Utama (User), tapi role-nya ADMIN (Admin nyasar ke portal User)
        if (!$isAdminDomain && $user->role === 'admin') {
            return $this->errorResponse('Akses ditolak! Akun Admin harus login melalui portal admin.', 403);
        }
        // ==================================================

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

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        // ==========================================
        // 1. RATE LIMITING LEVEL DATABASE (JEDA 2 MENIT)
        // ==========================================
        $existingReset = DB::table('password_reset_tokens')->where('email', $request->email)->first();
        
        if ($existingReset && Carbon::parse($existingReset->created_at)->addMinutes(2)->isFuture()) {
            // Jika token terakhir dibuat kurang dari 2 menit yang lalu, tolak request!
            $sisaWaktu = Carbon::parse($existingReset->created_at)->addMinutes(2)->diffInSeconds(now());
            return $this->errorResponse("Harap tunggu {$sisaWaktu} detik sebelum meminta kode OTP baru.", 429);
        }

        // 2. Generate Token Angka 6 Digit
        $token = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // 3. Simpan ke database bawaan Laravel
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token), // Hash demi keamanan database
                'created_at' => Carbon::now()
            ]
        );

        // 4. Kirim Email
        try {
            Mail::raw("Kode OTP untuk reset password Anda adalah: {$token}. Kode ini berlaku selama 15 menit. Jangan berikan kode ini kepada siapapun.", function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Kode OTP Reset Password - 24 Jam Menari');
            });
            return $this->successResponse(null, 'Kode OTP telah dikirim ke email Anda.');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengirim email. Silakan coba lagi nanti.', 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $resetData = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$resetData || !Hash::check($request->token, $resetData->token)) {
            return $this->errorResponse('Kode OTP tidak valid atau sudah usang.', 400);
        }

        // Cek kedaluwarsa (15 menit)
        if (Carbon::parse($resetData->created_at)->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return $this->errorResponse('Kode OTP telah kedaluwarsa. Silakan minta kode baru.', 400);
        }

        // Reset Password
        $user = User::where('email', $request->email)->first();
        $user->update(['password' => Hash::make($request->password)]);

        // Hapus token setelah terpakai
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return $this->successResponse(null, 'Password berhasil diubah. Silakan login dengan password baru.');
    }
}
