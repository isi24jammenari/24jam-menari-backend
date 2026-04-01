<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Venue;
use App\Models\TimeSlot;

class VenueSeeder extends Seeder
{
    public function run(): void
    {
        $venuesData = [
            [
                'id' => 'teater-besar-fest1',
                'name' => 'TEATER BESAR GENDHON HUMARDANI',
                'festival_name' => 'FESTIVAL 1',
                'slots' => [
                    ['id' => 'tb1-1', 'time' => '20.00 - 20.30', 'price' => 1350000],
                    ['id' => 'tb1-2', 'time' => '20.30 - 21.00', 'price' => 1350000],
                    ['id' => 'tb1-3', 'time' => '21.00 - 21.30', 'price' => 1350000],
                    ['id' => 'tb1-4', 'time' => '21.30 - 22.00', 'price' => 1350000],
                    ['id' => 'tb1-5', 'time' => '22.00 - 22.30', 'price' => 1350000],
                    ['id' => 'tb1-6', 'time' => '22.30 - 23.00', 'price' => 1350000],
                    ['id' => 'tb1-7', 'time' => '23.00 - 23.30', 'price' => 1350000],
                    ['id' => 'tb1-8', 'time' => '23.30 - 24.00', 'price' => 1350000],
                ]
            ],
            [
                'id' => 'teater-besar-fest2',
                'name' => 'TEATER BESAR GENDHON HUMARDANI',
                'festival_name' => 'FESTIVAL 2',
                'slots' => [
                    ['id' => 'tb2-1', 'time' => '10.00 - 10.20', 'price' => 1100000],
                    ['id' => 'tb2-2', 'time' => '10.20 - 10.40', 'price' => 1100000],
                    ['id' => 'tb2-3', 'time' => '10.40 - 11.00', 'price' => 1100000],
                    ['id' => 'tb2-4', 'time' => '11.00 - 11.20', 'price' => 1100000],
                    ['id' => 'tb2-5', 'time' => '11.20 - 11.40', 'price' => 1100000],
                    ['id' => 'tb2-6', 'time' => '11.40 - 12.00', 'price' => 1100000],
                ]
            ],
            [
                'id' => 'teater-kecil-fest2',
                'name' => 'TEATER KECIL KRT KUSUMA KESAWA',
                'festival_name' => 'FESTIVAL 2',
                'slots' => [
                    ['id' => 'tk2-1', 'time' => '22.00 - 22.20', 'price' => 1100000],
                    ['id' => 'tk2-2', 'time' => '22.20 - 22.40', 'price' => 1100000],
                    ['id' => 'tk2-3', 'time' => '22.40 - 23.00', 'price' => 1100000],
                    ['id' => 'tk2-4', 'time' => '23.00 - 23.20', 'price' => 1100000],
                    ['id' => 'tk2-5', 'time' => '23.20 - 23.40', 'price' => 1100000],
                    ['id' => 'tk2-6', 'time' => '23.40 - 24.00', 'price' => 1100000],
                ]
            ],
            [
                'id' => 'teater-kecil-fest3',
                'name' => 'TEATER KECIL KRT KUSUMA KESAWA',
                'festival_name' => 'FESTIVAL 3',
                'slots' => [
                    ['id' => 'tk3-1', 'time' => '10.00 - 10.20', 'price' => 800000],
                    ['id' => 'tk3-2', 'time' => '10.20 - 10.40', 'price' => 800000],
                    ['id' => 'tk3-3', 'time' => '10.40 - 11.00', 'price' => 800000],
                    ['id' => 'tk3-4', 'time' => '11.00 - 11.20', 'price' => 800000],
                    ['id' => 'tk3-5', 'time' => '11.20 - 11.40', 'price' => 800000],
                    ['id' => 'tk3-6', 'time' => '11.40 - 12.00', 'price' => 800000],
                ]
            ],
            [
                'id' => 'pendopo-fest3',
                'name' => 'PENDOPO GPH DJOYOKUSUMO',
                'festival_name' => 'FESTIVAL 3',
                'slots' => [
                    ['id' => 'p-1', 'time' => '09.00 - 09.20', 'price' => 800000],
                    ['id' => 'p-2', 'time' => '09.20 - 09.40', 'price' => 800000],
                    ['id' => 'p-3', 'time' => '09.40 - 10.00', 'price' => 800000],
                    ['id' => 'p-4', 'time' => '10.00 - 10.20', 'price' => 800000],
                    ['id' => 'p-5', 'time' => '10.20 - 10.40', 'price' => 800000],
                    ['id' => 'p-6', 'time' => '10.40 - 11.00', 'price' => 800000],
                    ['id' => 'p-7', 'time' => '11.00 - 11.20', 'price' => 800000],
                    ['id' => 'p-8', 'time' => '11.20 - 11.40', 'price' => 800000],
                    ['id' => 'p-9', 'time' => '11.40 - 12.00', 'price' => 800000],
                    ['id' => 'p-10', 'time' => '13.00 - 13.20', 'price' => 800000],
                    ['id' => 'p-11', 'time' => '13.20 - 13.40', 'price' => 800000],
                    ['id' => 'p-12', 'time' => '13.40 - 14.00', 'price' => 800000],
                    ['id' => 'p-13', 'time' => '14.00 - 14.20', 'price' => 800000],
                    ['id' => 'p-14', 'time' => '14.20 - 14.40', 'price' => 800000],
                    ['id' => 'p-15', 'time' => '14.40 - 15.00', 'price' => 800000],
                    ['id' => 'p-16', 'time' => '15.00 - 15.20', 'price' => 800000],
                    ['id' => 'p-17', 'time' => '15.20 - 15.40', 'price' => 800000],
                    ['id' => 'p-18', 'time' => '15.40 - 16.00', 'price' => 800000],
                    ['id' => 'p-19', 'time' => '16.00 - 16.20', 'price' => 800000],
                    ['id' => 'p-20', 'time' => '16.20 - 16.40', 'price' => 800000],
                    ['id' => 'p-21', 'time' => '16.40 - 17.00', 'price' => 800000],
                ]
            ],
            [
                'id' => 'teater-kapal-fest3',
                'name' => 'TEATER KAPAL',
                'festival_name' => 'FESTIVAL 3',
                'slots' => [
                    ['id' => 'tkapal-1', 'time' => '16.00 - 16.20', 'price' => 800000],
                    ['id' => 'tkapal-2', 'time' => '16.20 - 16.40', 'price' => 800000],
                    ['id' => 'tkapal-3', 'time' => '16.40 - 17.00', 'price' => 800000],
                    ['id' => 'tkapal-4', 'time' => '17.00 - 17.20', 'price' => 800000],
                    ['id' => 'tkapal-5', 'time' => '17.20 - 17.40', 'price' => 800000],
                ]
            ]
        ];

        foreach ($venuesData as $vData) {
            $venue = Venue::create([
                'id' => $vData['id'],
                'name' => $vData['name'],
                'festival_name' => $vData['festival_name'],
            ]);

            foreach ($vData['slots'] as $slot) {
                TimeSlot::create([
                    'id' => $slot['id'],
                    'venue_id' => $venue->id,
                    'time_range' => $slot['time'],
                    'price' => $slot['price'],
                    'is_booked' => false,
                ]);
            }
        }
    }
}