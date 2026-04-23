<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_stand_id')->constrained('tenant_stands')->cascadeOnDelete();
            
            // Payment Data
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->integer('amount');
            $table->string('payment_method')->nullable();
            $table->enum('status', ['pending', 'success', 'expired', 'failed'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            
            // Access Auth 
            $table->string('access_code')->nullable()->unique();
            
            // Form Tahap 1
            $table->string('pendaftar_name');
            $table->string('pendaftar_email');
            $table->string('phone');
            
            // Form Tahap 2 
            $table->string('tenant_name')->nullable();
            $table->string('product_type')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_bookings');
    }
};