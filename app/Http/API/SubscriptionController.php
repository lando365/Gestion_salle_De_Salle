<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller\API;
use App\Models\Subscription;
use App\Models\Member;
use App\Models\Notification;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::with(['member']);
        
        // Filtres
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }
        
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->where(function($q) use ($request) {
                $q->whereBetween('start_date', [$request->date_from, $request->date_to])
                  ->orWhereBetween('end_date', [$request->date_from, $request->date_to]);
            });
        }
        
        // Tri
        $sortField = $request->input('sort_field', 'start_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $subscriptions = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $subscriptions,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:members,id',
            'name' => 'required|string|max:255',
            'type' => 'required|in:monthly,quarterly,biannual,annual',
            'price' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'auto_renewal' => 'boolean',
            'status' => 'nullable|in:active,expired,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $subscription = Subscription::create([
            'member_id' => $request->member_id,
            'name' => $request->name,
            'type' => $request->type,
            'price' => $request->price,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'auto_renewal' => $request->auto_renewal ?? false,
            'status' => $request->status ?? 'active',
        ]);
        
        // Création de notification
        $member = Member::find($request->member_id);
        Notification::create([
            'notifiable_type' => get_class($member),
            'notifiable_id' => $member->id,
            'title' => 'Nouvel abonnement',
            'content' => "Votre abonnement {$request->name} a été créé avec succès. Il est valide du " . 
                Carbon::parse($request->start_date)->format('d/m/Y') . " au " . 
                Carbon::parse($request->end_date)->format('d/m/Y') . ".",
        ]);
        
        // Logging
        ActivityLog::log('created', $subscription);

        return response()->json([
            'success' => true,
            'message' => 'Abonnement créé avec succès',
            'data' => $subscription->load('member'),
        ], 201);
    }

    public function show($id)
    {
        $subscription = Subscription::with(['member', 'payments'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $subscription,
        ]);
    }

    public function update(Request $request, $id)
    {
        $subscription = Subscription::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:monthly,quarterly,biannual,annual',
            'price' => 'sometimes|required|numeric|min:0',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'auto_renewal' => 'boolean',
            'status' => 'sometimes|required|in:active,expired,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $oldStatus = $subscription->status;
        
        // Mise à jour des champs
        if ($request->has('name')) {
            $subscription->name = $request->name;
        }
        
        if ($request->has('type')) {
            $subscription->type = $request->type;
        }
        
        if ($request->has('price')) {
            $subscription->price = $request->price;
        }
        
        if ($request->has('start_date')) {
            $subscription->start_date = $request->start_date;
        }
        
        if ($request->has('end_date')) {
            $subscription->end_date = $request->end_date;
        }
        
        if ($request->has('auto_renewal')) {
            $subscription->auto_renewal = $request->auto_renewal;
        }
        
        if ($request->has('status')) {
            $subscription->status = $request->status;
        }
        
        $subscription->save();
        
        // Création de notification si changement de statut
        if ($request->has('status') && $oldStatus !== $request->status) {
            $member = $subscription->member;
            $statusMessages = [
                'active' => 'activé',
                'expired' => 'expiré',
                'cancelled' => 'annulé',
            ];
            
            Notification::create([
                'notifiable_type' => get_class($member),
                'notifiable_id' => $member->id,
                'title' => 'Mise à jour d\'abonnement',
                'content' => "Votre abonnement {$subscription->name} a été {$statusMessages[$request->status]}.",
            ]);
        }
        
        // Logging
        ActivityLog::log('updated', $subscription);

        return response()->json([
            'success' => true,
            'message' => 'Abonnement mis à jour avec succès',
            'data' => $subscription->load('member'),
        ]);
    }

    public function destroy($id)
    {
        $subscription = Subscription::findOrFail($id);
        
        // Soft delete
        $subscription->delete();
        
        // Logging
        ActivityLog::log('deleted', $subscription);

        return response()->json([
            'success' => true,
            'message' => 'Abonnement supprimé avec succès',
        ]);
    }

    public function getSubscriptionsByMember($memberId)
    {
        $member = Member::findOrFail($memberId);
        $subscriptions = $member->subscriptions()->with('payments')->get();
        
        return response()->json([
            'success' => true,
            'data' => $subscriptions,
        ]);
    }

    public function getExpiringSubscriptions(Request $request)
    {
        $days = $request->input('days', 30);
        $today = now();
        $future = now()->addDays($days);
        
        $expiringSubscriptions = Subscription::with('member')
            ->where('status', 'active')
            ->whereBetween('end_date', [$today, $future])
            ->orderBy('end_date')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $expiringSubscriptions,
        ]);
    }
}