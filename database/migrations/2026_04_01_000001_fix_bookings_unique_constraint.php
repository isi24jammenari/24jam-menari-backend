<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop constraint unique lama yang terlalu ketat
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique(['time_slot_id']);
        });

        // 2. Buat partial unique index di PostgreSQL:
        //    Hanya enforce keunikan untuk booking yang masih aktif (pending/success).
        //    Booking expired/failed boleh punya time_slot_id yang sama — artinya
        //    slot tersebut sudah dibebaskan dan boleh dibooking ulang.
        DB::statement('
            CREATE UNIQUE INDEX bookings_active_slot_unique
            ON bookings (time_slot_id)
            WHERE status IN (\'pending\', \'success\')
        ');
    }

    public function down(): void
    {
        // Rollback: hapus partial index, kembalikan unique biasa
        DB::statement('DROP INDEX IF EXISTS bookings_active_slot_unique');

        Schema::table('bookings', function (Blueprint $table) {
            $table->unique('time_slot_id');
        });
    }
};
