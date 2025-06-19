<?php

namespace Database\Seeders;

use App\Models\Equipment;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class EquipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $equipments = [
            [
                'name' => 'Tapis de course TechnoGym',
                'description' => 'Tapis de course professionnel avec diverses inclinaisons et programmes.',
                'purchase_date' => Carbon::now()->subMonths(6),
                'purchase_price' => 2500.00,
                'status' => 'available',
                'last_maintenance_date' => Carbon::now()->subMonths(1),
                'next_maintenance_date' => Carbon::now()->addMonths(2),
            ],
            [
                'name' => 'Vélo elliptique ProForm',
                'description' => 'Vélo elliptique avec 15 niveaux de résistance et moniteur cardiaque.',
                'purchase_date' => Carbon::now()->subMonths(8),
                'purchase_price' => 1800.00,
                'status' => 'available',
                'last_maintenance_date' => Carbon::now()->subMonths(2),
                'next_maintenance_date' => Carbon::now()->addMonths(1),
            ],
            [
                'name' => 'Banc de musculation multifonction',
                'description' => 'Banc réglable avec support pour haltères et accessoires.',
                'purchase_date' => Carbon::now()->subMonths(4),
                'purchase_price' => 1200.00,
                'status' => 'available',
                'last_maintenance_date' => Carbon::now()->subMonths(1),
                'next_maintenance_date' => Carbon::now()->addMonths(5),
            ],
            [
                'name' => 'Presse à cuisses',
                'description' => 'Machine de musculation pour les jambes et les fessiers.',
                'purchase_date' => Carbon::now()->subMonths(10),
                'purchase_price' => 2200.00,
                'status' => 'available',
                'last_maintenance_date' => Carbon::now()->subDays(15),
                'next_maintenance_date' => Carbon::now()->addMonths(3),
            ],
            [
                'name' => 'Rowing machine Concept2',
                'description' => 'Rameur professionnel pour un entraînement cardio complet.',
                'purchase_date' => Carbon::now()->subMonths(12),
                'purchase_price' => 1500.00,
                'status' => 'maintenance',
                'last_maintenance_date' => Carbon::now()->subMonths(3),
                'next_maintenance_date' => Carbon::now()->addDays(5),
            ],
            [
                'name' => 'Set d\'haltères (2-20kg)',
                'description' => 'Ensemble complet d\'haltères de différents poids.',
                'purchase_date' => Carbon::now()->subMonths(3),
                'purchase_price' => 800.00,
                'status' => 'available',
                'last_maintenance_date' => null,
                'next_maintenance_date' => null,
            ],
            [
                'name' => 'Kettlebells (4-20kg)',
                'description' => 'Ensemble de kettlebells pour les exercices fonctionnels.',
                'purchase_date' => Carbon::now()->subMonths(5),
                'purchase_price' => 600.00,
                'status' => 'available',
                'last_maintenance_date' => null,
                'next_maintenance_date' => null,
            ],
            [
                'name' => 'TRX Suspension Trainer',
                'description' => 'Système d\'entraînement par suspension pour des exercices fonctionnels.',
                'purchase_date' => Carbon::now()->subMonths(2),
                'purchase_price' => 350.00,
                'status' => 'available',
                'last_maintenance_date' => null,
                'next_maintenance_date' => null,
            ],
            [
                'name' => 'Stepper Matrix',
                'description' => 'Stepper professionnel pour l\'entraînement cardiovasculaire.',
                'purchase_date' => Carbon::now()->subMonths(9),
                'purchase_price' => 1800.00,
                'status' => 'out_of_service',
                'last_maintenance_date' => Carbon::now()->subMonths(1),
                'next_maintenance_date' => null,
            ],
            [
                'name' => 'Tapis de yoga (lot de 15)',
                'description' => 'Lot de tapis de yoga anti-dérapants pour les cours collectifs.',
                'purchase_date' => Carbon::now()->subMonths(1),
                'purchase_price' => 450.00,
                'status' => 'available',
                'last_maintenance_date' => null,
                'next_maintenance_date' => null,
            ],
        ];

        foreach ($equipments as $equipment) {
            Equipment::create($equipment);
        }
    }
}