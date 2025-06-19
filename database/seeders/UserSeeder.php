<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Création d'un utilisateur admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Création d'un utilisateur manager
        User::create([
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'email_verified_at' => now(),
        ]);

        // Création de 3 coachs
        $coaches = [
            [
                'name' => 'Pierre Dupont',
                'email' => 'pierre@example.com',
                'role' => 'coach',
                'phone' => '0612345678',
            ],
            [
                'name' => 'Marie Martin',
                'email' => 'marie@example.com',
                'role' => 'coach',
                'phone' => '0687654321',
            ],
            [
                'name' => 'Jean Durand',
                'email' => 'jean@example.com',
                'role' => 'coach',
                'phone' => '0654321987',
            ]
        ];

        foreach ($coaches as $coach) {
            User::create([
                'name' => $coach['name'],
                'email' => $coach['email'],
                'password' => Hash::make('password'),
                'role' => $coach['role'],
                'phone' => $coach['phone'],
                'email_verified_at' => now(),
            ]);
        }
    }
}