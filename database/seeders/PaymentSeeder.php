<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Subscription;
use App\Models\Payment;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Faker\Factory as Faker;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('fr_FR');
        
        // Générer des paiements pour les abonnements
        $subscriptions = Subscription::all();
        
        foreach ($subscriptions as $subscription) {
            // Déterminer le statut du paiement
            $status = 'paid';
            if ($subscription->status === 'cancelled') {
                $status = $faker->randomElement(['paid', 'refunded']);
            } elseif ($subscription->status === 'expired') {
                $status = 'paid';
            }
            
            // Date de paiement (généralement au début de l'abonnement)
            $paymentDate = $subscription->start_date;
            
            Payment::create([
                'member_id' => $subscription->member_id,
                'subscription_id' => $subscription->id,
                'invoice_number' => Payment::generateInvoiceNumber(),
                'amount' => $subscription->price,
                'payment_date' => $paymentDate,
                'payment_method' => $faker->randomElement(['cash', 'credit_card', 'bank_transfer']),
                'status' => $status,
                'notes' => $faker->optional(0.2)->sentence(),
                'created_at' => $paymentDate,
            ]);
            
            // Pour les abonnements plus longs, parfois ajouter des paiements supplémentaires (services additionnels)
            if (in_array($subscription->type, ['quarterly', 'biannual', 'annual']) && $faker->boolean(30)) {
                $additionalPaymentDate = Carbon::parse($paymentDate)->addDays($faker->numberBetween(15, 60));
                
                if ($additionalPaymentDate <= now()) {
                    Payment::create([
                        'member_id' => $subscription->member_id,
                        'subscription_id' => null, // Pas lié à l'abonnement (service ponctuel)
                        'invoice_number' => Payment::generateInvoiceNumber(),
                        'amount' => $faker->randomFloat(2, 10, 50),
                        'payment_date' => $additionalPaymentDate,
                        'payment_method' => $faker->randomElement(['cash', 'credit_card', 'bank_transfer']),
                        'status' => 'paid',
                        'notes' => 'Service supplémentaire',
                        'created_at' => $additionalPaymentDate,
                    ]);
                }
            }
        }
        
        // Générer quelques paiements en attente pour des membres actifs
        $activeMembers = Member::where('status', 'active')->get();
        $pendingPaymentsCount = min(10, $activeMembers->count());
        
        for ($i = 0; $i < $pendingPaymentsCount; $i++) {
            $member = $activeMembers->random();
            
            Payment::create([
                'member_id' => $member->id,
                'subscription_id' => null,
                'invoice_number' => Payment::generateInvoiceNumber(),
                'amount' => $faker->randomFloat(2, 30, 100),
                'payment_date' => $faker->dateTimeBetween('-3 days', '+7 days'),
                'payment_method' => $faker->randomElement(['cash', 'credit_card', 'bank_transfer']),
                'status' => 'pending',
                'notes' => 'Paiement en attente',
                'created_at' => now(),
            ]);
        }
    }
}