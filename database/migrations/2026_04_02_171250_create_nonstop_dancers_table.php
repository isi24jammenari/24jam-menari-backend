<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nonstop_dancers', function (Blueprint $table) {
            $table->id();
            
            // Data Pribadi
            $table->string('name');
            $table->string('email')->unique(); // 1 Email hanya bisa mendaftar 1 kali
            $table->string('phone');
            
            // Data Karya & Pendamping
            $table->string('masterpiece_title');
            $table->text('companions_identity'); 
            
            // File Storage (Menyimpan File ID atau URL dari Google Drive)
            $table->string('health_cert_file_id');
            $table->string('cv_file_id');
            $table->string('photo_file_id');
            $table->string('video_file_id');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nonstop_dancers');
    }
};