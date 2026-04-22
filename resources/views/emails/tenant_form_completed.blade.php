<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; }
        .header { background: #2e7d32; color: #ffffff; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .info-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .info-table td:first-child { font-weight: bold; width: 150px; }
        .btn-wa { 
            display: block; 
            background: #25d366; 
            color: white; 
            text-decoration: none; 
            text-align: center; 
            padding: 12px; 
            font-weight: bold; 
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer { font-size: 12px; color: #777; margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>PENDAFTARAN TENANT SELESAI</h2>
        </div>
        <div class="content">
            <p>Selamat! Pendaftaran tenant Anda telah <strong>Terverifikasi Sepenuhnya</strong>. Data Anda sudah masuk ke sistem database panitia.</p>
            
            <table class="info-table">
                <tr>
                    <td>Nomor Stand</td>
                    <td>Stand #{{ $booking->stand->stand_number }}</td>
                </tr>
                <tr>
                    <td>Nama Tenant</td>
                    <td>{{ $booking->tenant_name }}</td>
                </tr>
                <tr>
                    <td>Jenis Produk</td>
                    <td>{{ $booking->product_type }}</td>
                </tr>
                <tr>
                    <td>Kode Akses</td>
                    <td><strong>{{ $booking->access_code }}</strong></td>
                </tr>
            </table>

            <div style="background: #e8f5e9; padding: 15px; border-radius: 5px; font-size: 14px;">
                <strong>Catatan Penting:</strong><br>
                1. Simpan Kode Akses Anda untuk login ke Dashboard Tenant.<br>
                2. Sesuai aturan PDF, loading in dilakukan pada <strong>28 April 2026 pukul 15.00 WIB</strong>.<br>
                3. Harap membawa identitas diri saat verifikasi di lokasi.
            </div>

            <p>Jika ada kendala teknis atau pertanyaan lebih lanjut, silakan hubungi tim bazar melalui WhatsApp:</p>
            
            <a href="https://wa.me/6281331073894" class="btn-wa">HUBUNGI PANITIA BAZAAR</a>
        </div>
        <div class="footer">
            <p>Tim Panitia Bazaar 24 Jam Menari #20<br>Institut Seni Indonesia Surakarta</p>
        </div>
    </div>
</body>
</html>