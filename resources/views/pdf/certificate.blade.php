<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>E-Sertifikat</title>
    <style>
        /* Hilangkan semua margin bawaan kertas (Full Bleed) */
        @page {
            margin: 0px;
        }
        body {
            margin: 0px;
            padding: 0px;
            font-family: Arial, Helvetica, sans-serif;
        }
        
        /* Lapisan Background Gambar */
        .bg-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        /* Lapisan Teks di Atas Gambar */
        .content {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            text-align: center; /* Asumsi teks di sertifikat Anda rata tengah */
        }
        
        /* =========================================================
           AREA EDITING KOORDINAT
           Ubah nilai 'top' (persentase) di bawah ini untuk 
           menggeser teks naik/turun agar pas dengan garis di JPG Anda.
           ========================================================= */
        
        .nama-penari {
            position: absolute;
            top: 45%; /* <-- GESER NAIK TURUN DI SINI */
            width: 100%;
            font-size: 42px;
            font-weight: bold;
            color: #000000;
            text-transform: uppercase;
        }
        
        .nama-grup {
            position: absolute;
            top: 55%; /* <-- GESER NAIK TURUN DI SINI */
            width: 100%;
            font-size: 24px;
            font-weight: bold;
            color: #333333;
        }
        
        .venue {
            position: absolute;
            top: 62%; /* <-- GESER NAIK TURUN DI SINI */
            width: 100%;
            font-size: 18px;
            color: #555555;
        }
    </style>
</head>
<body>
    <img src="{{ public_path('template_sertifikat.jpg') }}" class="bg-image" />

    <div class="content">
        <div class="nama-penari">{{ $name }}</div>
        <div class="nama-grup">{{ $group_name }}</div>
        <div class="venue">Venue: {{ $venue }}</div>
    </div>
</body>
</html>