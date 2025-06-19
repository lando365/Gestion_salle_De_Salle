<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Subscription;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Faker\Factory as Faker;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('fr_FR');
        $members = Member::where('status', 'active')->get();
        
        $subscriptionTypes = [
            'monthly' => [
                'name' => 'Abonnement Mensuel',
                'price' => 49.99,
                'duration' => 30, // jours
            ],
            'quarterly' => [
                'name' => 'Abonnement Trimestriel',
                'price' => 129.99,
                'duration' => 90, // jours
            ],
            'biannual' => [
                'name' => 'Abonnement Semestriel',
                'price' => 239.99,
                'duration' => 180, // jours
            ],
            'annual' => [
                'name' => 'Abonnement Annuel',
                'price' => 399.99,
                'duration' => 365, // jours
            ],
        ];
        
        foreach ($members as $member) {
            // Nombre d'abonnements par membre (1 à 3)
            $subscriptionCount = $faker->numberBetween(1, 3);
            
            for ($i = 0; $i < $subscriptionCount; $i++) {
                $type = $faker->randomElement(array_keys($subscriptionTypes));
                $subscriptionInfo = $subscriptionTypes[$type];
                
                // Définir si c'est un abonnement actif ou expiré
                $isActive = $i === 0 ? true : $faker->boolean(30);
                
                if ($isActive) {
                    // Abonnement actif
                    $startDate = $faker->dateTimeBetween('-' . $subscriptionInfo['duration'] . ' days', 'now');
                    $endDate = Carbon::instance($startDate)->addDays($subscriptionInfo['duration']);
                    $status = 'active';
                } else {
                    // Abonnement expiré
                    $startDate = $faker->dateTimeBetween('-1 year', '-' . ($subscriptionInfo['duration'] + 30) . ' days');
                    $endDate = Carbon::instance($startDate)->addDays($subscriptionInfo['duration']);
                    $status = $faker->randomElement(['expired', 'cancelled']);
                }
                
                Subscription::create([
                    'member_id' => $member->id,
                    'name' => $subscriptionInfo['name'],
                    'type' => $type,
                    'price' => $subscriptionInfo['price'],
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'auto_renewal' => $faker->boolean(),
                    'status' => $status,
                    'created_at' => $startDate,
                ]);
            }
        }
    }
}