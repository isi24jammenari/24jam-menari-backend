<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->json('revised_data');
            // Status: pending (menunggu), approved (disetujui & ditimpa), rejected (ditolak)
            $table->string('status')->default('pending'); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_revisions');
    }
};