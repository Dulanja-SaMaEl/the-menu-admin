<?php

namespace Database\Seeders;

use App\Models\Driver;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DriverSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $drivers = [
            [
                'name' => 'Dulanja Abeysinghe',
                'email' => 'dulanja150abeysinghe@gmail.com',
                'password' => Hash::make('password123'),
                'phone' => '+94 77 123 4567',
                'status' => 'available',
                'vehicle_type' => 'Motorbike',
                'vehicle_number' => 'WP-BEX-1234',
            ],
            [
                'name' => 'Chandupa Jayalath',
                'email' => 'Chandupajayalath20@gmail.com',
                'password' => Hash::make('password123'),
                'phone' => '+94 71 999 8888',
                'status' => 'available',
                'vehicle_type' => 'Bike',
                'vehicle_number' => 'WP-BEE-5544',
            ],
            [
                'name' => 'Saman Kumara',
                'email' => 'saman@example.com',
                'password' => Hash::make('password123'),
                'phone' => '+94 76 123 4567',
                'status' => 'available',
                'vehicle_type' => 'Bike',
                'vehicle_number' => 'BE-4521',
            ],
            [
                'name' => 'Nimal Siripala',
                'email' => 'nimal@example.com',
                'password' => Hash::make('password123'),
                'phone' => '+94 77 234 5678',
                'status' => 'available',
                'vehicle_type' => 'Tuk',
                'vehicle_number' => 'QH-8892',
            ],
        ];

        foreach ($drivers as $driver) {
            Driver::updateOrCreate(['email' => $driver['email']], $driver);
        }
    }
}
