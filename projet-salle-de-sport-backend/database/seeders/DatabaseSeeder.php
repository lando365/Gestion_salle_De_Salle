<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@gym.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Florentin',
            'email' => 'florentin@gym.com',
            'password' => Hash::make('florentin123'),
            'role' => 'manager',
        ]);

        // Manager user
        User::create([
            'name' => 'Manager',
            'email' => 'manager@gym.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
        ]);


        // Services
        Service::create(['name' => 'Musculation', 'duration' => 60, 'price' => 20.00]);
        Service::create(['name' => 'Cardio', 'duration' => 45, 'price' => 15.00]);
        Service::create(['name' => 'Cours Collectif', 'duration' => 60, 'price' => 25.00]);
    }
}