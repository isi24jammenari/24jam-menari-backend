<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performances', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID
            
            // Mengikat ke tabel bookings
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            
            // ==================================================
            // DATA FORMULIR PEMENTASAN (Sesuai PDF)
            // Semua diset nullable() demi mengakomodir fitur DRAFT
            // ==================================================
            
            $table->string('group_name')->nullable();          // 1. Nama Peserta / Grup
            $table->string('contact_person')->nullable();      // 2. Contact Person (Instansi/Kategori CP)
            $table->string('cp_name')->nullable();             // 3. Nama CP
            
            // 4. Festival (Venue/Waktu) -> Sudah terwakili oleh relasi booking_id ke time_slots
            
            $table->enum('category', ['Anak-anak', 'Remaja', 'Dewasa', 'Disabilitas'])->nullable(); // 6. Kategori
            $table->text('supporters')->nullable();            // 6. Pendukung Karya (Jumlah/Detail)
            $table->string('dance_title')->nullable();         // 7. Judul Karya
            $table->string('duration')->nullable();            // 8. Durasi Karya
            $table->text('synopsis')->nullable();              // 9. Sinopsis
            $table->string('arrival_departure')->nullable();   // 10. Kedatangan & Kepulangan
            $table->enum('music_type', ['Live', 'Playback'])->nullable(); // 11. Keterangan Musik
            $table->text('instruments')->nullable();           // 12. Alat Musik (Jika Live)
            $table->text('property_setting')->nullable();      // 13. Property / Setting
            $table->text('certificate_names')->nullable();     // 14. Nama Lengkap untuk Sertifikat
            
            // Status Gatekeeper
            $table->enum('status', ['draft', 'completed'])->default('draft');
            
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('performances');
    }
};