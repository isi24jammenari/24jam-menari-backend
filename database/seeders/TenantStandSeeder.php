<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantStandSeeder extends Seeder
{
    public function run(): void
    {
        $stands = [];
        
        // REVISI: Membuat 18 Stand dengan harga Rp 1 untuk testing production
        for ($i = 1; $i <= 18; $i++) {
            $stands[] = [
                'id' => Str::uuid()->toString(),
                'stand_number' => $i,
                'price' => 1, // HARGA TESTING
                'is_booked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Kosongkan tabel dulu jika tidak ingin menggunakan migrate:fresh
        DB::table('tenant_stands')->truncate();
        DB::table('tenant_stands')->insert($stands);
    }
}