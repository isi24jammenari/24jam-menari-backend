<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; }
        .header { background: #1a1a1a; color: #ffffff; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .access-code { 
            background: #f4f4f4; 
            border: 2px dashed #333; 
            padding: 15px; 
            text-align: center; 
            font-size: 24px; 
            font-weight: bold; 
            letter-spacing: 2px;
            margin: 20px 0;
        }
        .btn { 
            display: block; 
            width: 100%; 
            background: #ff4500; 
            color: white; 
            text-decoration: none; 
            text-align: center; 
            padding: 15px 0; 
            font-weight: bold; 
            border-radius: 5px;
        }
        .footer { font-size: 12px; color: #777; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>PEMBAYARAN TENANT BERHASIL</h2>
        </div>
        <div class="content">
            <p>Halo <strong>{{ $booking->pendaftar_name }}</strong>,</p>
            <p>Pembayaran untuk stand bazaar 24 Jam Menari 2026 telah kami terima. Berikut adalah detail transaksi Anda:</p>
            
            <ul>
                <li><strong>Order ID:</strong> {{ $booking->midtrans_order_id }}</li>
                <li><strong>Total Bayar:</strong> Rp {{ number_format($booking->amount, 0, ',', '.') }}</li>
                <li><strong>Metode:</strong> {{ strtoupper($booking->payment_method) }}</li>
            </ul>

            <p style="margin-bottom: 5px;"><strong>KODE AKSES ANDA:</strong></p>
            <div class="access-code">
                {{ $booking->access_code }}
            </div>

            <div style="background: #fff3cd; padding: 10px; border-left: 4px solid #ffecb5; margin-bottom: 20px;">
                <strong>Tutorial Login:</strong><br>
                Jika sesi browser Anda terputus, Anda dapat masuk kembali ke sistem dengan mengunjungi subdomain <strong>tenant.24jammenariisisurakarta.com</strong> dan memasukkan kode akses di atas pada kolom "Login Tenant".
            </div>

            <p>Silakan klik tombol di bawah ini untuk <strong>melengkapi data tenant</strong> (Nama Tenant & Jenis Produk) agar pendaftaran dianggap sah oleh panitia:</p>
            
            <a href="https://tenant.24jammenariisisurakarta.com/form?order_id={{ $booking->midtrans_order_id }}" class="btn">LENGKAPI FORMULIR SEKARANG</a>
        </div>
        <div class="footer">
            <p>&copy; 2026 Panitia 24 Jam Menari #20 - ISI Surakarta.<br>Email ini dikirim otomatis oleh sistem. Jangan dibalas.</p>
        </div>
    </div>
</body>
</html>