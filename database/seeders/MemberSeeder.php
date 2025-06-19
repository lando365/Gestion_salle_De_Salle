<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Faker\Factory as Faker;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('fr_FR');
        
        // Création de membres avec différents statuts
        for ($i = 0; $i < 50; $i++) {
            $status = $faker->randomElement(['active', 'inactive', 'pending']);
            $birthDate = $faker->dateTimeBetween('-70 years', '-18 years');
            
            Member::create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'email' => $faker->unique()->safeEmail,
                'phone' => $faker->phoneNumber,
                'birth_date' => $birthDate,
                'address' => $faker->address,
                'emergency_contact' => $faker->phoneNumber,
                'status' => $status,
                'notes' => $faker->optional(0.3)->paragraph(2),
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
            ]);
        }
    }
}