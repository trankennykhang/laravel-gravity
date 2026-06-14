<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Clear existing file first
        $path = User::getStoragePath();
        if (file_exists($path)) {
            unlink($path);
        }

        // 1. Create Default Admin User
        User::create([
            'name' => 'Gravity Administrator',
            'email' => 'admin@gravity.com',
            'password' => Hash::make('password')
        ]);

        // 2. Create several other mock staff users to test search, sort, and pagination
        $staff = [
            ['name' => 'Dr. Elizabeth Shaw', 'email' => 'elizabeth.shaw@gravity.com'],
            ['name' => 'Capt. Joseph Cooper', 'email' => 'joseph.cooper@gravity.com'],
            ['name' => 'Dr. Amelia Brand', 'email' => 'amelia.brand@gravity.com'],
            ['name' => 'Dr. Mann', 'email' => 'mann@gravity.com'],
            ['name' => 'Murph Cooper', 'email' => 'murph@gravity.com'],
            ['name' => 'TARS', 'email' => 'tars@gravity.com'],
            ['name' => 'CASE', 'email' => 'case@gravity.com'],
            ['name' => 'Dr. Ryan Stone', 'email' => 'ryan.stone@gravity.com'],
            ['name' => 'Matt Kowalski', 'email' => 'matt.kowalski@gravity.com'],
            ['name' => 'Mark Watney', 'email' => 'mark.watney@gravity.com'],
            ['name' => 'Melissa Lewis', 'email' => 'melissa.lewis@gravity.com'],
            ['name' => 'Rick Martinez', 'email' => 'rick.martinez@gravity.com'],
            ['name' => 'Chris Beck', 'email' => 'chris.beck@gravity.com'],
            ['name' => 'Beth Johanssen', 'email' => 'beth.johanssen@gravity.com']
        ];

        foreach ($staff as $person) {
            User::create([
                'name' => $person['name'],
                'email' => $person['email'],
                'password' => Hash::make('staffpass123')
            ]);
        }

        $this->command->info("Successfully seeded admin & staff users into JSON database!");
    }
}
