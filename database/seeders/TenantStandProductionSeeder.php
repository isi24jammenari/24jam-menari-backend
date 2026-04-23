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
        for ($i = 1; $i <= 16; $i++) {
            $stands[] = [
                'id' => Str::uuid()->toString(),
                'stand_number' => $i,
                'price' => 1800000, // HARGA ASLI PRODUCTION
                'is_booked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('tenant_stands')->insert($stands);
    }
}