<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f4f5; color: #3f3f46; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header { background-color: #18181b; color: #ffffff; text-align: center; padding: 30px 20px; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: 1px; }
        .content { padding: 30px 40px; }
        .content h2 { color: #16a34a; font-size: 20px; margin-top: 0; }
        .invoice-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .invoice-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #cbd5e1; }
        .invoice-row:last-child { border-bottom: none; font-weight: bold; font-size: 18px; color: #0f172a; margin-top: 10px; padding-top: 15px;}
        .label { color: #64748b; font-size: 14px; }
        .value { color: #0f172a; font-size: 14px; font-weight: 600; text-align: right; }
        .btn { display: inline-block; background-color: #dc2626; color: #ffffff; text-decoration: none; padding: 14px 24px; border-radius: 6px; font-weight: bold; text-align: center; width: 100%; box-sizing: border-box; margin-top: 20px; }
        .footer { text-align: center; padding: 20px; color: #94a3b8; font-size: 12px; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>24 JAM MENARI</h1>
            <p style="margin: 5px 0 0; color: #a1a1aa; font-size: 14px;">Institut Seni Indonesia Surakarta</p>
        </div>
        <div class="content">
            <h2>Pendaftaran Berhasil!</h2>
            <p>Halo <strong>{{ $user->name }}</strong>,</p>
            <p>Selamat bergabung! Akun Anda telah berhasil dibuat, dan pembayaran slot pementasan Anda telah LUNAS. Berikut adalah detail booking Anda:</p>
            
            <div class="invoice-box">
                <div class="invoice-row">
                    <span class="label">ID Booking</span>
                    <span class="value">{{ strtoupper(explode('-', $booking->id)[0]) }}</span>
                </div>
                <div class="invoice-row">
                    <span class="label">Email Akun</span>
                    <span class="value">{{ $user->email }}</span>
                </div>
                <div class="invoice-row">
                    <span class="label">Venue</span>
                    <span class="value">{{ $booking->timeSlot->venue->name ?? '-' }}</span>
                </div>
                <div class="invoice-row">
                    <span class="label">Jam Pementasan</span>
                    <span class="value">{{ $booking->timeSlot->time_range ?? '-' }}</span>
                </div>
                <div class="invoice-row">
                    <span class="label">Total Pembayaran</span>
                    <span class="value">Rp {{ number_format($booking->amount, 0, ',', '.') }}</span>
                </div>
            </div>

            <p style="font-size: 14px; color: #475569;">Langkah selanjutnya, silakan masuk ke dashboard untuk <strong>mengisi formulir karya pementasan dan melengkapi data anggota kelompok</strong> Anda.</p>

            <a href="https://24jammenariisisurakarta.com/dashboard/user" class="btn">Masuk ke Dashboard Sekarang</a>
        </div>
        <div class="footer">
            Email ini dibuat otomatis oleh sistem.<br>
            © 2026 24 Jam Menari ISI Surakarta. Semua hak dilindungi.
        </div>
    </div>
</body>
</html>