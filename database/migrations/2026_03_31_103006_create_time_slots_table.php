<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->string('id')->primary(); // ID string seperti 'tb1-1'
            $table->string('venue_id');
            $table->foreign('venue_id')->references('id')->on('venues')->cascadeOnDelete();
            
            $table->string('time_range'); // '20.00 - 20.30'
            $table->integer('price');
            $table->boolean('is_booked')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
