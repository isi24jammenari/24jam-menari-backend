<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Undangan</title>
    <style>
        /* RESET & BASE */
        @page {
            margin: 1.5cm 2cm 2.5cm 2cm; /* Margin kertas (Top, Right, Bottom, Left) */
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            position: relative;
        }
        p { margin-top: 0; margin-bottom: 10px; }
        .justify { text-align: justify; }
        .indent { text-indent: 40px; }
        .clear { clear: both; }

        /* HEADER (LOGO & KOP SURAT) */
        .header-container { width: 100%; margin-bottom: 20px; }
        .header-logo { width: 100px; float: left; } /* Sesuaikan ukuran logo */
        .header-text { margin-left: 120px; padding-top: 10px; }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; padding: 0; }

        /* ISI KONTEN (DETAIL KEGIATAN) */
        .detail-table { margin-left: 40px; margin-bottom: 15px; width: 90%; border-collapse: collapse; }
        .detail-table td { vertical-align: top; padding: 0; }

        /* TANDA TANGAN ATAS (DEKAN & KAJUR) */
        .sign-row-1 { width: 100%; margin-top: 30px; position: relative; }
        .sign-left { width: 50%; float: left; text-align: center; }
        .sign-right { width: 50%; float: right; text-align: center; }
        .sign-img { height: 70px; margin-top: 5px; margin-bottom: 5px; }
        
        /* SPASI KOSONG UNTUK DEKAN (KARENA TANPA TTD) */
        .empty-sign-space { height: 80px; }

        /* TANDA TANGAN BAWAH (KETUA HTD) */
        .sign-row-2 { width: 100%; margin-top: 20px; position: relative; text-align: center; }
        .sign-center { width: 60%; margin: 0 auto; position: relative; text-align: center; }
        .stamp-img { 
            position: absolute; 
            left: 15%; /* Geser stempel ke kiri TTD */
            top: 20px; 
            width: 120px; 
            z-index: -1; 
            opacity: 0.85; 
        }

        /* FOOTER (KUNING) */
        .footer {
            position: fixed;
            bottom: -1cm; /* Nempel ke ujung bawah kertas */
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 10pt;
            color: #d4af37; /* Warna Kuning Emas gelap agar kontras di kertas putih */
            line-height: 1.3;
        }
        .footer p { margin: 0; }
        .footer strong { color: #b8860b; }
    </style>
</head>
<body>

    <div class="header-container">
        <img src="{{ public_path('images/24jammenari.png') }}" class="header-logo" alt="Logo 24 Jam Menari">
        <div class="clear"></div>
    </div>

    <table class="header-table">
        <tr>
            <td style="width: 10%;">No</td>
            <td style="width: 2%;">:</td>
            <td>{{ str_pad($booking->performance->invitation_number, 3, '0', STR_PAD_LEFT) }}/HTD 20/PB/2026</td>
        </tr>
        <tr>
            <td>Hal</td>
            <td>:</td>
            <td>Undangan Pergelaran WDD 29 April Th. 2026</td>
        </tr>
        <tr>
            <td>Lamp</td>
            <td>:</td>
            <td>-</td>
        </tr>
    </table>

    <br>

    <p>
        Kepada Yth<br>
        <strong>{{ $booking->performance->group_name }}</strong><br>
        Di Tempat
    </p>

    <p>Dengan Hormat,</p>
    <div class="justify">
        <p class="indent">Memperingati Hari Tari Dunia Tahun 2026, Jurusan Tari Fakultas Seni Pertunjukan Institut Seni Indonesia (ISI) Surakarta akan menyelenggarakan event <strong>24 JAM MENARI ISI SURAKARTA</strong> yang ke-20 tahun dengan mengusung tema <em>“Tanpa Batas : Menembus Medan Budaya”</em>. Kegiatan tersebut akan diselenggarakan pada:</p>

        <table class="detail-table">
            <tr><td style="width: 15%;">Hari</td><td style="width: 3%;">:</td><td>Jumat-Sabtu</td></tr>
            <tr><td>Tanggal</td><td>:</td><td>29 April &ndash; 30 April 2026</td></tr>
            <tr><td>Pukul</td><td>:</td><td>06.00 WIB s.d. 06.00 WIB (24 Jam)</td></tr>
            <tr><td>Tempat</td><td>:</td><td>Institut Seni Indonesia Surakarta<br>Jl. Ki Hadjar Dewantara No. 19 Kentingan Jebres Surakarta</td></tr>
        </table>

        <p class="indent">Sehubungan dengan kegiatan tersebut, ISI Surakarta mengundang <strong>{{ $booking->performance->group_name }}</strong> untuk ikut berpartisipasi.</p>
        <p class="indent">Demikian permohonan ini kami sampaikan. Atas Perhatiannya kami haturkan terimakasih.</p>
    </div>

    <div style="text-align: right; margin-top: 30px;">
        Surakarta, 30 Maret 2026
    </div>

    <div style="text-align: left; margin-top: 10px;">
        Mengetahui,
    </div>

    <div class="sign-row-1">
        <div class="sign-left">
            Dekan Fakultas Seni Pertunjukan
            <div class="empty-sign-space"></div> <strong>Dr. Dr Aris setiawan, S.Sn., M.Sn.</strong>
        </div>
        
        <div class="sign-right">
            Ketua Jurusan Tari
            <br>
            <img src="{{ public_path('images/ttd_jurusan.png') }}" class="sign-img" alt="TTD Kajur">
            <br>
            <strong>Dr. Matheus Wasi Bantolo, S.Sn, M.Sn.</strong>
        </div>
    </div>
    <div class="clear"></div>

    <div class="sign-row-2">
        <div class="sign-center">
            Hormat Kami;
            <br>
            Ketua Hari Tari Dunia 2026
            <br>
            <img src="{{ public_path('images/stampel.png') }}" class="stamp-img" alt="Stempel">
            <img src="{{ public_path('images/ttd_htd.png') }}" class="sign-img" alt="TTD HTD">
            <br>
            <strong>Prof. Dr. Maryono, S.Kar., M.Hum.</strong>
        </div>
    </div>
    <div class="clear"></div>

    <div class="footer">
        <strong>SEKRETARIAT</strong><br>
        Jurusan Tari Institut Seni Indonesia Surakarta<br>
        Jl.Ki Hadjar Dewantara No. 19, Kentingan, Jebres, Surakarta 57126<br>
        email: 24jammenari.isisolo@gmail.com/menari24jam.isisolo@gmail.com
    </div>

</body>
</html>