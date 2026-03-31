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
            
            // Data Formulir Pementasan
            $table->string('group_name');
            $table->string('city');
            $table->string('contact_name');
            $table->string('whatsapp_number');
            $table->string('dance_title');
            
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('performances');
    }
};
