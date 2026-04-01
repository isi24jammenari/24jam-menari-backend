<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            
            $table->string('group_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('cp_name')->nullable();
            $table->enum('category', ['Anak-anak', 'Remaja', 'Dewasa', 'Disabilitas'])->nullable();
            $table->text('supporters')->nullable();
            
            // Kolom Dynamic List (Diubah menjadi JSON)
            $table->json('works')->nullable(); // Menampung array of {title, duration}
            $table->json('instruments')->nullable(); // Menampung array of string
            $table->json('certificate_names')->nullable(); // Menampung array of string
            
            $table->text('synopsis')->nullable();
            $table->string('arrival_departure')->nullable();
            $table->enum('music_type', ['Live', 'Playback'])->nullable();
            $table->text('property_setting')->nullable();
            
            $table->enum('status', ['draft', 'completed'])->default('draft');
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('performances');
    }
};