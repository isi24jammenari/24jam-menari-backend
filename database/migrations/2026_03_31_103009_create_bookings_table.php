<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID
            
            // Nullable karena user mendaftar akun SETELAH bayar
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Unique memastikan 1 slot hanya bisa dimiliki 1 booking yang valid
            $table->string('time_slot_id')->unique();
            $table->foreign('time_slot_id')->references('id')->on('time_slots')->cascadeOnDelete();
            
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->integer('amount');
            $table->string('payment_method')->nullable();
            
            // Status alur pendaftaran
            $table->enum('status', ['pending', 'success', 'expired', 'failed'])->default('pending');
            $table->timestamp('expires_at')->nullable(); // Kunci TTL 15 menit
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
