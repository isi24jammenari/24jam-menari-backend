<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantStandProductionSeeder extends Seeder
{
    public function run(): void
    {
        $stands = [];
        
        // REVISI: Membuat 18 Stand 
        for ($i = 1; $i <= 18; $i++) {
            $stands[] = [
                'id' => Str::uuid()->toString(),
                'stand_number' => $i,
                'price' => 1050000, // HARGA ASLI PRODUCTION
                'is_booked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        // Kosongkan tabel dulu agar tidak terjadi duplikasi
        DB::table('tenant_stands')->truncate();
        DB::table('tenant_stands')->insert($stands);
    }
}