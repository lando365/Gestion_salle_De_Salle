<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller\API;
use App\Models\Payment;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\Notification;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['member', 'subscription']);
        
        // Filtres
        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }
        
        if ($request->has('subscription_id')) {
            $query->where('subscription_id', $request->subscription_id);
        }
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('payment_date', [$request->date_from, $request->date_to]);
        }
        
        // Tri
        $sortField = $request->input('sort_field', 'payment_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $payments = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:members,id',
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,credit_card,bank_transfer,other',
            'status' => 'nullable|in:paid,pending,cancelled,refunded',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Générer le numéro de facture
        $invoiceNumber = Payment::generateInvoiceNumber();
        
        $payment = Payment::create([
            'member_id' => $request->member_id,
            'subscription_id' => $request->subscription_id,
            'invoice_number' => $invoiceNumber,
            'amount' => $request->amount,
            'payment_date' => $request->payment_date,
            'payment_method' => $request->payment_method,
            'status' => $request->status ?? 'paid',
            'notes' => $request->notes,
        ]);
        
        // Si lié à un abonnement et statut payé, mettre à jour le statut de l'abonnement
        if ($request->subscription_id && ($request->status ?? 'paid') === 'paid') {
            $subscription = Subscription::find($request->subscription_id);
            $subscription->update(['status' => 'active']);
        }
        
        // Création de notification
        $member = Member::find($request->member_id);
        Notification::create([
            'notifiable_type' => get_class($member),
            'notifiable_id' => $member->id,
            'title' => 'Nouveau paiement',
            'content' => "Votre paiement de {$request->amount}€ a été enregistré avec succès. Numéro de facture: {$invoiceNumber}",
        ]);
        
        // Logging
        ActivityLog::log('created', $payment);

        return response()->json([
            'success' => true,
            'message' => 'Paiement créé avec succès',
            'data' => $payment->load(['member', 'subscription']),
        ], 201);
    }

    public function show($id)
    {
        $payment = Payment::with(['member', 'subscription'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    public function update(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'amount' => 'sometimes|required|numeric|min:0',
            'payment_date' => 'sometimes|required|date',
            'payment_method' => 'sometimes|required|in:cash,credit_card,bank_transfer,other',
            'status' => 'sometimes|required|in:paid,pending,cancelled,refunded',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $oldStatus = $payment->status;
        
        // Mise à jour des champs
        if ($request->has('amount')) {
            $payment->amount = $request->amount;
        }
        
        if ($request->has('payment_date')) {
            $payment->payment_date = $request->payment_date;
        }
        
        if ($request->has('payment_method')) {
            $payment->payment_method = $request->payment_method;
        }
        
        if ($request->has('status')) {
            $payment->status = $request->status;
        }
        
        if ($request->has('notes')) {
            $payment->notes = $request->notes;
        }
        
        $payment->save();
        
        // Si le statut a changé et est lié à un abonnement
        if ($request->has('status') && $oldStatus !== $request->status && $payment->subscription_id) {
            $subscription = $payment->subscription;
            
            if ($request->status === 'paid') {
                $subscription->update(['status' => 'active']);
            } elseif (in_array($request->status, ['cancelled', 'refunded'])) {
                // Vérifier s'il y a d'autres paiements actifs pour cet abonnement
                $hasOtherPayments = Payment::where('subscription_id', $subscription->id)
                    ->where('id', '!=', $payment->id)
                    ->where('status', 'paid')
                    ->exists();
                    
                if (!$hasOtherPayments) {
                    $subscription->update(['status' => 'cancelled']);
                }
            }
        }
        
        // Création de notification si changement de statut
        if ($request->has('status') && $oldStatus !== $request->status) {
            $member = $payment->member;
            $statusMessages = [
                'paid' => 'marqué comme payé',
                'pending' => 'en attente de paiement',
                'cancelled' => 'annulé',
                'refunded' => 'remboursé',
            ];
            
            Notification::create([
                'notifiable_type' => get_class($member),
                'notifiable_id' => $member->id,
                'title' => 'Mise à jour de paiement',
                'content' => "Votre paiement de {$payment->amount}€ (facture {$payment->invoice_number}) a été {$statusMessages[$request->status]}.",
            ]);
        }
        
        // Logging
        ActivityLog::log('updated', $payment);

        return response()->json([
            'success' => true,
            'message' => 'Paiement mis à jour avec succès',
            'data' => $payment->load(['member', 'subscription']),
        ]);
    }

    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);
        
        // Soft delete
        $payment->delete();
        
        // Logging
        ActivityLog::log('deleted', $payment);

        return response()->json([
            'success' => true,
            'message' => 'Paiement supprimé avec succès',
        ]);
    }

    public function generateInvoice($id)
    {
        $payment = Payment::with(['member', 'subscription'])->findOrFail($id);
        
        // Ici, vous pouvez implémenter la logique pour générer un PDF de facture
        // Utiliser une bibliothèque comme Dompdf, mPDF ou Snappy PDF
        
        // Pour cet exemple, je vais juste retourner les données pour le template de facture
        $member = $payment->member;
        $subscription = $payment->subscription;
        
        $invoiceData = [
            'invoice_number' => $payment->invoice_number,
            'payment_date' => $payment->payment_date->format('d/m/Y'),
            'member' => [
                'name' => $member->full_name,
                'email' => $member->email,
                'address' => $member->address,
            ],
            'payment' => [
                'amount' => $payment->amount,
                'method' => $payment->payment_method,
                'status' => $payment->status,
            ],
            'subscription' => $subscription ? [
                'name' => $subscription->name,
                'type' => $subscription->type,
                'start_date' => $subscription->start_date->format('d/m/Y'),
                'end_date' => $subscription->end_date->format('d/m/Y'),
            ] : null,
            'gym_info' => [
                'name' => 'Salle de Sport Pro',
                'address' => '95 Rue Gatti De Gamond, 1180 Bruxelles',
                'phone' => '02 332 1166',
                'email' => 'contact@sallesportpro.be',
                'website' => 'www.sallesportpro.be',
                'tax_id' => 'BE0123456789',
            ]
        ];
        
        return response()->json([
            'success' => true,
            'data' => $invoiceData,
        ]);
    }

    public function getFinancialStats(Request $request)
    {
        // Période (par défaut: année courante)
        $year = $request->input('year', date('Y'));
        $startDate = Carbon::createFromDate($year, 1, 1)->startOfDay();
        $endDate = Carbon::createFromDate($year, 12, 31)->endOfDay();
        
        // Revenus totaux
        $totalRevenue = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->sum('amount');
        
        // Revenus par mois
        $revenueByMonth = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthStart = Carbon::createFromDate($year, $month, 1)->startOfDay();
            $monthEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
            
            $monthlyRevenue = Payment::whereBetween('payment_date', [$monthStart, $monthEnd])
                ->where('status', 'paid')
                ->sum('amount');
                
            $revenueByMonth[] = [
                'month' => Carbon::createFromDate($year, $month, 1)->format('M'),
                'revenue' => $monthlyRevenue,
            ];
        }
        
        // Revenus par méthode de paiement
        $revenueByMethod = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'paid')
            ->select('payment_method')
            ->selectRaw('sum(amount) as total')
            ->groupBy('payment_method')
            ->get()
            ->map(function ($item) {
                return [
                    'method' => $item->payment_method,
                    'total' => $item->total,
                ];
            });
            
        // Nombre de paiements par statut
        $paymentsByStatus = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->select('status')
            ->selectRaw('count(*) as count')
            ->selectRaw('sum(amount) as total')
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'count' => $item->count,
                    'total' => $item->total,
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => $totalRevenue,
                'revenue_by_month' => $revenueByMonth,
                'revenue_by_method' => $revenueByMethod,
                'payments_by_status' => $paymentsByStatus,
            ],
        ]);
    }
}