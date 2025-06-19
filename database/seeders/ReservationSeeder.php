<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Service;
use App\Models\User;
use App\Models\Reservation;
use App\Models\Equipment;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Faker\Factory as Faker;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('fr_FR');
        
        $members = Member::where('status', 'active')->get();
        $services = Service::where('active', true)->get();
        $coaches = User::where('role', 'coach')->get();
        $equipments = Equipment::where('status', 'available')->get();
        
        // Créer des réservations passées
        $this->createPastReservations($faker, $members, $services, $coaches, $equipments);
        
        // Créer des réservations futures
        $this->createFutureReservations($faker, $members, $services, $coaches, $equipments);
    }
    
    private function createPastReservations($faker, $members, $services, $coaches, $equipments)
    {
        // Générer 100 réservations passées (il y a 1 à 30 jours)
        for ($i = 0; $i < 100; $i++) {
            $member = $faker->randomElement($members);
            $service = $faker->randomElement($services);
            $coach = $faker->optional(0.8)->randomElement($coaches);
            
            $startDate = $faker->dateTimeBetween('-30 days', '-1 day');
            $startDateTime = Carbon::instance($startDate)->setTime(
                $faker->numberBetween(8, 20), // Heures entre 8h et 20h
                $faker->randomElement([0, 15, 30, 45]) // Minutes (0, 15, 30 ou 45)
            );
            
            $endDateTime = (clone $startDateTime)->addMinutes($service->duration);
            
            // Déterminer le statut en fonction de la date
            if ($startDateTime->isPast() && $endDateTime->isPast()) {
                $status = $faker->randomElement(['completed', 'cancelled', 'no_show']);
            } else {
                $status = 'scheduled';
            }
            
            $reservation = Reservation::create([
                'member_id' => $member->id,
                'service_id' => $service->id,
                'coach_id' => $coach ? $coach->id : null,
                'start_time' => $startDateTime,
                'end_time' => $endDateTime,
                'notes' => $faker->optional(0.3)->sentence(),
                'status' => $status,
                'created_at' => (clone $startDateTime)->subDays($faker->numberBetween(1, 7)),
            ]);
            
            // Attacher des équipements si nécessaire
            if ($faker->boolean(70)) {
                $randomEquipments = $equipments->random($faker->numberBetween(1, 3));
                $reservation->equipments()->attach($randomEquipments->pluck('id')->toArray());
            }
        }
    }
    
    private function createFutureReservations($faker, $members, $services, $coaches, $equipments)
    {
        // Générer 50 réservations futures (dans les 14 prochains jours)
        for ($i = 0; $i < 50; $i++) {
            $member = $faker->randomElement($members);
            $service = $faker->randomElement($services);
            $coach = $faker->optional(0.8)->randomElement($coaches);
            
            $startDate = $faker->dateTimeBetween('now', '+14 days');
            $startDateTime = Carbon::instance($startDate)->setTime(
                $faker->numberBetween(8, 20), // Heures entre 8h et 20h
                $faker->randomElement([0, 15, 30, 45]) // Minutes (0, 15, 30 ou 45)
            );
            
            $endDateTime = (clone $startDateTime)->addMinutes($service->duration);
            
            $reservation = Reservation::create([
                'member_id' => $member->id,
                'service_id' => $service->id,
                'coach_id' => $coach ? $coach->id : null,
                'start_time' => $startDateTime,
                'end_time' => $endDateTime,
                'notes' => $faker->optional(0.3)->sentence(),
                'status' => 'scheduled',
                'created_at' => (clone $startDateTime)->subDays($faker->numberBetween(1, 7)),
            ]);
            
            // Attacher des équipements si nécessaire
            if ($faker->boolean(70)) {
                $randomEquipments = $equipments->random($faker->numberBetween(1, 3));
                $reservation->equipments()->attach($randomEquipments->pluck('id')->toArray());
            }
        }
    }
}