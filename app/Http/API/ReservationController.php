<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller\API;
use App\Models\Reservation;
use App\Models\Member;
use App\Models\Service;
use App\Models\User;
use App\Models\Equipment;
use App\Models\Notification;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $query = Reservation::with(['member', 'service', 'coach', 'equipments']);
        
        // Filtres
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }
        
        if ($request->has('service_id')) {
            $query->where('service_id', $request->service_id);
        }
        
        if ($request->has('coach_id')) {
            $query->where('coach_id', $request->coach_id);
        }
        
        if ($request->has('date')) {
            $date = Carbon::parse($request->date);
            $query->whereDate('start_time', $date);
        }
        
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->whereBetween('start_time', [$startDate, $endDate]);
        }
        
        // Tri
        $sortField = $request->input('sort_field', 'start_time');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $reservations = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $reservations,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'member_id' => 'required|exists:members,id',
            'service_id' => 'required|exists:services,id',
            'coach_id' => 'nullable|exists:users,id',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'notes' => 'nullable|string',
            'equipment_ids' => 'nullable|array',
            'equipment_ids.*' => 'exists:equipments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Vérifier la disponibilité du service
        $service = Service::findOrFail($request->service_id);
        if (!$service->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Le service n\'est pas disponible.',
            ], 422);
        }

        // Vérifier la disponibilité du coach
        if ($request->has('coach_id')) {
            $coach = User::findOrFail($request->coach_id);
            
            // Vérifier si le coach est déjà réservé à cette heure
            $coachBusy = Reservation::where('coach_id', $coach->id)
                ->where(function($query) use ($request) {
                    $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                          ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                          ->orWhere(function($q) use ($request) {
                              $q->where('start_time', '<=', $request->start_time)
                                ->where('end_time', '>=', $request->end_time);
                          });
                })
                ->where('status', 'scheduled')
                ->exists();
                
            if ($coachBusy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le coach sélectionné n\'est pas disponible à cette heure.',
                ], 422);
            }
        }

        // Vérifier la disponibilité des équipements
        if ($request->has('equipment_ids') && !empty($request->equipment_ids)) {
            $equipments = Equipment::whereIn('id', $request->equipment_ids)->get();
            
            foreach ($equipments as $equipment) {
                if (!$equipment->isAvailable()) {
                    return response()->json([
                        'success' => false,
                        'message' => "L'équipement {$equipment->name} n'est pas disponible.",
                    ], 422);
                }
                
                // Vérifier si l'équipement est déjà réservé à cette heure
                $equipmentBusy = Reservation::whereHas('equipments', function($query) use ($equipment) {
                    $query->where('equipments.id', $equipment->id);
                })
                ->where(function($query) use ($request) {
                    $query->whereBetween('start_time', [$request->start_time, $request->end_time])
                          ->orWhereBetween('end_time', [$request->start_time, $request->end_time])
                          ->orWhere(function($q) use ($request) {
                              $q->where('start_time', '<=', $request->start_time)
                                ->where('end_time', '>=', $request->end_time);
                          });
                })
                ->where('status', 'scheduled')
                ->exists();
                
                if ($equipmentBusy) {
                    return response()->json([
                        'success' => false,
                        'message' => "L'équipement {$equipment->name} est déjà réservé à cette heure.",
                    ], 422);
                }
            }
        }

        $reservation = Reservation::create([
            'member_id' => $request->member_id,
            'service_id' => $request->service_id,
            'coach_id' => $request->coach_id,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'notes' => $request->notes,
            'status' => 'scheduled',
        ]);

        // Attacher les équipements
        if ($request->has('equipment_ids') && !empty($request->equipment_ids)) {
            $reservation->equipments()->attach($request->equipment_ids);
            
            // Marquer les équipements comme en cours d'utilisation
            Equipment::whereIn('id', $request->equipment_ids)
                ->update(['status' => 'in_use']);
        }
        
        // Création de notification
        $member = Member::find($request->member_id);
        Notification::create([
            'notifiable_type' => get_class($member),
            'notifiable_id' => $member->id,
            'title' => 'Nouvelle réservation',
            'content' => "Votre réservation pour {$service->name} le " . Carbon::parse($request->start_time)->format('d/m/Y à H:i') . " a été créée avec succès.",
        ]);
        
        // Logging
        ActivityLog::log('created', $reservation);

        return response()->json([
            'success' => true,
            'message' => 'Réservation créée avec succès',
            'data' => $reservation->load(['member', 'service', 'coach', 'equipments']),
        ], 201);
    }

    public function show($id)
    {
        $reservation = Reservation::with(['member', 'service', 'coach', 'equipments'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $reservation,
        ]);
    }

    public function update(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);
        
        // Vérifier si la réservation est déjà terminée
        if ($reservation->status === 'completed' || $reservation->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier une réservation terminée ou annulée.',
            ], 422);
        }
        
        $validator = Validator::make($request->all(), [
            'service_id' => 'sometimes|required|exists:services,id',
            'coach_id' => 'nullable|exists:users,id',
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date|after:start_time',
            'notes' => 'nullable|string',
            'status' => 'sometimes|required|in:scheduled,completed,cancelled,no_show',
            'equipment_ids' => 'nullable|array',
            'equipment_ids.*' => 'exists:equipments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Mise à jour du statut
        if ($request->has('status')) {
            $reservation->status = $request->status;
            
            // Si annulé ou terminé, libérer les équipements
            if (in_array($request->status, ['cancelled', 'completed', 'no_show'])) {
                foreach ($reservation->equipments as $equipment) {
                    $equipment->markAsAvailable();
                }
            }
        }

        // Mise à jour des champs de base
        if ($request->has('service_id')) {
            $reservation->service_id = $request->service_id;
        }
        
        if ($request->has('coach_id')) {
            $reservation->coach_id = $request->coach_id;
        }
        
        if ($request->has('start_time')) {
            $reservation->start_time = $request->start_time;
        }
        
        if ($request->has('end_time')) {
            $reservation->end_time = $request->end_time;
        }
        
        if ($request->has('notes')) {
            $reservation->notes = $request->notes;
        }
        
        $reservation->save();
        
        // Mise à jour des équipements
        if ($request->has('equipment_ids')) {
            // Libérer les anciens équipements
            foreach ($reservation->equipments as $equipment) {
                $equipment->markAsAvailable();
            }
            
            // Attacher les nouveaux équipements
            $reservation->equipments()->sync($request->equipment_ids);
            
            // Marquer les nouveaux équipements comme en cours d'utilisation
            if ($reservation->status === 'scheduled') {
                Equipment::whereIn('id', $request->equipment_ids)
                    ->update(['status' => 'in_use']);
            }
        }
        
        // Création de notification pour changement de statut
        $statusMessages = [
            'completed' => 'terminée',
            'cancelled' => 'annulée',
            'no_show' => 'marquée comme absence',
        ];
        
        if ($request->has('status') && array_key_exists($request->status, $statusMessages)) {
            $member = $reservation->member;
            $service = $reservation->service;
            
            Notification::create([
                'notifiable_type' => get_class($member),
                'notifiable_id' => $member->id,
                'title' => 'Mise à jour de réservation',
                'content' => "Votre réservation pour {$service->name} le " . Carbon::parse($reservation->start_time)->format('d/m/Y à H:i') . " a été {$statusMessages[$request->status]}.",
            ]);
        }
        
        // Logging
        ActivityLog::log('updated', $reservation);

        return response()->json([
            'success' => true,
            'message' => 'Réservation mise à jour avec succès',
            'data' => $reservation->load(['member', 'service', 'coach', 'equipments']),
        ]);
    }

    public function destroy($id)
    {
        $reservation = Reservation::findOrFail($id);
        
        // Libérer les équipements
        foreach ($reservation->equipments as $equipment) {
            $equipment->markAsAvailable();
        }
        
        // Détacher les équipements avant suppression
        $reservation->equipments()->detach();
        
        // Soft delete
        $reservation->delete();
        
        // Logging
        ActivityLog::log('deleted', $reservation);

        return response()->json([
            'success' => true,
            'message' => 'Réservation supprimée avec succès',
        ]);
    }

    public function getCalendarEvents(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');
        
        $reservations = Reservation::with(['member', 'service', 'coach'])
            ->whereBetween('start_time', [$start, $end])
            ->get();
            
        $events = $reservations->map(function ($reservation) {
            $color = [
                'scheduled' => '#2196F3', // Bleu
                'completed' => '#4CAF50', // Vert
                'cancelled' => '#F44336', // Rouge
                'no_show' => '#FF9800', // Orange
            ][$reservation->status];
            
            return [
                'id' => $reservation->id,
                'title' => $reservation->member->full_name . ' - ' . $reservation->service->name,
                'start' => $reservation->start_time,
                'end' => $reservation->end_time,
                'color' => $color,
                'extendedProps' => [
                    'member' => $reservation->member,
                    'service' => $reservation->service,
                    'coach' => $reservation->coach,
                    'status' => $reservation->status,
                ],
            ];
        });
        
        return response()->json($events);
    }

    public function getUpcomingReservations()
    {
        $upcomingReservations = Reservation::with(['member', 'service', 'coach'])
            ->where('start_time', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('start_time')
            ->take(10)
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $upcomingReservations,
        ]);
    }

    public function getReservationStats()
    {
        // Statistiques globales
        $totalReservations = Reservation::count();
        $completedReservations = Reservation::where('status', 'completed')->count();
        $cancelledReservations = Reservation::where('status', 'cancelled')->count();
        $upcomingReservations = Reservation::where('start_time', '>=', now())
            ->where('status', 'scheduled')
            ->count();
            
        // Réservations par service (top 5)
        $reservationsByService = Reservation::select('service_id')
            ->selectRaw('count(*) as total')
            ->groupBy('service_id')
            ->orderByDesc('total')
            ->take(5)
            ->with('service')
            ->get()
            ->map(function ($item) {
                return [
                    'service' => $item->service->name,
                    'total' => $item->total,
                ];
            });
            
        // Réservations par jour (cette semaine)
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        
        $reservationsByDay = [];
        for ($date = $startOfWeek; $date <= $endOfWeek; $date->addDay()) {
            $count = Reservation::whereDate('start_time', $date->format('Y-m-d'))->count();
            $reservationsByDay[] = [
                'day' => $date->format('D'),
                'date' => $date->format('Y-m-d'),
                'count' => $count,
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'total' => $totalReservations,
                'completed' => $completedReservations,
                'cancelled' => $cancelledReservations,
                'upcoming' => $upcomingReservations,
                'by_service' => $reservationsByService,
                'by_day' => $reservationsByDay,
            ],
        ]);
    }
}