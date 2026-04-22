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
        
        // Membuat 16 Stand dengan harga Rp 1 untuk testing production
        for ($i = 1; $i <= 16; $i++) {
            $stands[] = [
                'id' => Str::uuid()->toString(),
                'stand_number' => $i,
                'price' => 1, // HARGA TESTING
                'is_booked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('tenant_stands')->insert($stands);
    }
}