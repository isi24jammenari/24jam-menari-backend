<!DOCTYPE html>
<html>
<head>
    <title>Surat Undangan</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; line-height: 1.6; margin: 40px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 30px; }
        .content { font-size: 14px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>PANITIA 24 JAM MENARI</h2>
        <p>Surakarta, Jawa Tengah</p>
    </div>
    
    <div class="content">
        <p>Nomor: 001/UND/24JM/{{ date('Y') }}</p>
        <p>Hal: Undangan Pementasan</p>
        <br>
        <p>Kepada Yth.<br>
        <strong>{{ $booking->performance->contact_name }}</strong><br>
        Perwakilan dari <strong>{{ $booking->performance->group_name }}</strong><br>
        di {{ $booking->performance->city }}</p>

        <p>Dengan hormat,</p>
        <p>Kami mengundang sanggar/kelompok Saudara untuk menampilkan karya <strong>"{{ $booking->performance->dance_title }}"</strong> pada:</p>
        <ul>
            <li><strong>Tempat:</strong> {{ $booking->timeSlot->venue->name }}</li>
            <li><strong>Waktu:</strong> {{ $booking->timeSlot->time_range }} WIB</li>
        </ul>
        <p>Demikian undangan ini kami sampaikan. Terima kasih atas partisipasinya.</p>
        <br><br>
        <p>Hormat kami,</p>
        <br><br>
        <p><strong>Panitia 24 Jam Menari</strong></p>
    </div>
</body>
</html>