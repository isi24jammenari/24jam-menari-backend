<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom data diri di tabel users
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number')->nullable()->after('email');
            $table->string('institution_name')->nullable()->after('phone_number');
            $table->text('address')->nullable()->after('institution_name');
        });

        // 2. Ubah kolom tabel performances menjadi nullable & tambah kolom status
        Schema::table('performances', function (Blueprint $table) {
            $table->string('group_name')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->string('contact_name')->nullable()->change();
            $table->string('whatsapp_number')->nullable()->change();
            $table->string('dance_title')->nullable()->change();
            
            $table->enum('status', ['draft', 'completed'])->default('draft')->after('dance_title');
        });
    }

    public function down(): void
    {
        Schema::table('performances', function (Blueprint $table) {
            $table->dropColumn('status');
            
            // Kembalikan ke NOT NULL (opsional, tergantung kebutuhan rollback)
            $table->string('group_name')->nullable(false)->change();
            $table->string('city')->nullable(false)->change();
            $table->string('contact_name')->nullable(false)->change();
            $table->string('whatsapp_number')->nullable(false)->change();
            $table->string('dance_title')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone_number', 'institution_name', 'address']);
        });
    }
};