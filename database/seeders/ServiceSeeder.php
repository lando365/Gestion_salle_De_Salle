<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'Cours de Fitness',
                'description' => 'Séance de fitness en groupe pour améliorer votre endurance et tonicité.',
                'price' => 15.00,
                'duration' => 60, // minutes
                'capacity' => 15,
                'active' => true,
            ],
            [
                'name' => 'Séance de Musculation',
                'description' => 'Séance individuelle de musculation avec un coach dédié.',
                'price' => 25.00,
                'duration' => 45, // minutes
                'capacity' => 1,
                'active' => true,
            ],
            [
                'name' => 'Yoga',
                'description' => 'Cours de yoga pour tous niveaux pour améliorer votre flexibilité et votre équilibre.',
                'price' => 18.00,
                'duration' => 75, // minutes
                'capacity' => 10,
                'active' => true,
            ],
            [
                'name' => 'Spinning',
                'description' => 'Cours de vélo intensif pour brûler des calories et améliorer votre cardio.',
                'price' => 12.00,
                'duration' => 45, // minutes
                'capacity' => 12,
                'active' => true,
            ],
            [
                'name' => 'CrossFit',
                'description' => 'Entraînement fonctionnel à haute intensité pour développer votre force et votre endurance.',
                'price' => 20.00,
                'duration' => 60, // minutes
                'capacity' => 8,
                'active' => true,
            ],
            [
                'name' => 'Pilates',
                'description' => 'Méthode d\'entraînement physique qui renforce les muscles profonds et améliore la posture.',
                'price' => 18.00,
                'duration' => 60, // minutes
                'capacity' => 10,
                'active' => true,
            ],
            [
                'name' => 'Circuit Training',
                'description' => 'Séance d\'entraînement en circuit pour travailler l\'ensemble du corps et brûler des calories.',
                'price' => 15.00,
                'duration' => 45, // minutes
                'capacity' => 12,
                'active' => true,
            ],
            [
                'name' => 'Coaching Personnalisé',
                'description' => 'Séance individuelle avec un coach pour atteindre vos objectifs spécifiques.',
                'price' => 35.00,
                'duration' => 60, // minutes
                'capacity' => 1,
                'active' => true,
            ],
            [
                'name' => 'Boxe',
                'description' => 'Cours de boxe pour développer votre coordination, votre force et votre endurance.',
                'price' => 22.00,
                'duration' => 60, // minutes
                'capacity' => 8,
                'active' => false, // service inactif pour test
            ],
        ];

        foreach ($services as $service) {
            Service::create($service);
        }
    }
}