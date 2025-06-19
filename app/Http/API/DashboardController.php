<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Reservation;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getManagerStats()
    {
        // Statistiques des membres
        $totalMembers = Member::count();
        $activeMembers = Member::where('status', 'active')->count();
        $newMembersThisMonth = Member::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
            
        // Statistiques des réservations
        $totalReservations = Reservation::count();
        $upcomingReservations = Reservation::where('start_time', '>=', now())
            ->where('status', 'scheduled')
            ->count();
        $reservationsToday = Reservation::whereDate('start_time', today())
            ->count();
            
        // Statistiques financières
        $revenueThisMonth = Payment::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->where('status', 'paid')
            ->sum('amount');
            
        $revenueLastMonth = Payment::whereMonth('payment_date', now()->subMonth()->month)
            ->whereYear('payment_date', now()->subMonth()->year)
            ->where('status', 'paid')
            ->sum('amount');
            
        $percentChange = $revenueLastMonth > 0 
            ? (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100 
            : 100;
            
        // Services les plus populaires
        $popularServices = Reservation::selectRaw('service_id, count(*) as count')
            ->with('service:id,name')
            ->groupBy('service_id')
            ->orderByDesc('count')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'service' => $item->service->name,
                    'count' => $item->count,
                ];
            });
            
        // Réservations par jour (cette semaine)
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        
        $reservationsByDay = [];
        for ($date = clone $startOfWeek; $date <= $endOfWeek; $date->addDay()) {
            $count = Reservation::whereDate('start_time', $date->format('Y-m-d'))->count();
            $reservationsByDay[] = [
                'day' => $date->format('D'),
                'date' => $date->format('Y-m-d'),
                'count' => $count,
            ];
        }
        
        // Réservations imminentes
        $upcomingReservationsList = Reservation::with(['member', 'service', 'coach'])
            ->where('start_time', '>=', now())
            ->where('start_time', '<=', now()->addHours(24))
            ->where('status', 'scheduled')
            ->orderBy('start_time')
            ->take(5)
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => [
                'members' => [
                    'total' => $totalMembers,
                    'active' => $activeMembers,
                    'new_this_month' => $newMembersThisMonth,
                ],
                'reservations' => [
                    'total' => $totalReservations,
                    'upcoming' => $upcomingReservations,
                    'today' => $reservationsToday,
                ],
                'finances' => [
                    'revenue_this_month' => $revenueThisMonth,
                    'revenue_last_month' => $revenueLastMonth,
                    'percent_change' => round($percentChange, 2),
                ],
                'popular_services' => $popularServices,
                'reservations_by_day' => $reservationsByDay,
                'upcoming_reservations' => $upcomingReservationsList,
            ],
        ]);
    }

    public function getAdminStats()
    {
        // Toutes les statistiques du gestionnaire
        $managerStats = $this->getManagerStats()->getData(true)['data'];
        
        // Statistiques supplémentaires pour administrateur
        
        // Utilisateurs par rôle
        $usersByRole = User::selectRaw('role, count(*) as count')
            ->groupBy('role')
            ->get()
            ->map(function ($item) {
                return [
                    'role' => $item->role,
                    'count' => $item->count,
                ];
            });
            
        // Revenus par mois (derniers 12 mois)
        $revenueByMonth = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $revenue = Payment::whereMonth('payment_date', $month->month)
                ->whereYear('payment_date', $month->year)
                ->where('status', 'paid')
                ->sum('amount');
                
            $revenueByMonth[] = [
                'month' => $month->format('M Y'),
                'revenue' => $revenue,
            ];
        }
        
        // Taux d'occupation des services
        $services = Service::all();
        $serviceOccupancy = [];
        
        foreach ($services as $service) {
            $totalReservations = Reservation::where('service_id', $service->id)
                ->count();
                
            $completedReservations = Reservation::where('service_id', $service->id)
                ->where('status', 'completed')
                ->count();
                
            $cancelledReservations = Reservation::where('service_id', $service->id)
                ->where('status', 'cancelled')
                ->count();
                
            $noShowReservations = Reservation::where('service_id', $service->id)
                ->where('status', 'no_show')
                ->count();
                
            $occupancyRate = $totalReservations > 0 
                ? ($completedReservations / $totalReservations) * 100 
                : 0;
                
            $serviceOccupancy[] = [
                'service' => $service->name,
                'total' => $totalReservations,
                'completed' => $completedReservations,
                'cancelled' => $cancelledReservations,
                'no_show' => $noShowReservations,
                'occupancy_rate' => round($occupancyRate, 2),
            ];
        }
        
        // Activité du système
        $recentActivity = \App\Models\ActivityLog::with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => array_merge($managerStats, [
                'users' => [
                    'by_role' => $usersByRole,
                ],
                'finances' => array_merge($managerStats['finances'], [
                    'by_month' => $revenueByMonth,
                ]),
                'services' => [
                    'occupancy' => $serviceOccupancy,
                ],
                'activity' => $recentActivity,
            ]),
        ]);
    }

    public function getCoachStats()
    {
        $coach = auth()->user();
        
        // Statistiques des réservations du coach
        $totalReservations = Reservation::where('coach_id', $coach->id)->count();
        $completedReservations = Reservation::where('coach_id', $coach->id)
            ->where('status', 'completed')
            ->count();
            
        $upcomingReservations = Reservation::where('coach_id', $coach->id)
            ->where('start_time', '>=', now())
            ->where('status', 'scheduled')
            ->count();
            
        $cancelledReservations = Reservation::where('coach_id', $coach->id)
            ->where('status', 'cancelled')
            ->count();
            
        $noShowReservations = Reservation::where('coach_id', $coach->id)
            ->where('status', 'no_show')
            ->count();
            
        // Réservations par jour (cette semaine)
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        
        $reservationsByDay = [];
        for ($date = clone $startOfWeek; $date <= $endOfWeek; $date->addDay()) {
            $count = Reservation::where('coach_id', $coach->id)
                ->whereDate('start_time', $date->format('Y-m-d'))
                ->count();
                
            $reservationsByDay[] = [
                'day' => $date->format('D'),
                'date' => $date->format('Y-m-d'),
                'count' => $count,
            ];
        }
        
        // Réservations imminentes
        $upcomingReservationsList = Reservation::with(['member', 'service'])
            ->where('coach_id', $coach->id)
            ->where('start_time', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('start_time')
            ->take(10)
            ->get();
            
        // Services les plus enseignés
        $teachedServices = Reservation::where('coach_id', $coach->id)
            ->selectRaw('service_id, count(*) as count')
            ->with('service:id,name')
            ->groupBy('service_id')
            ->orderByDesc('count')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'service' => $item->service->name,
                    'count' => $item->count,
                ];
            });
            
        return response()->json([
            'success' => true,
            'data' => [
                'reservations' => [
                    'total' => $totalReservations,
                    'completed' => $completedReservations,
                    'upcoming' => $upcomingReservations,
                    'cancelled' => $cancelledReservations,
                    'no_show' => $noShowReservations,
                ],
                'reservations_by_day' => $reservationsByDay,
                'upcoming_reservations' => $upcomingReservationsList,
                'teached_services' => $teachedServices,
            ],
        ]);
    }

    public function getStats(Request $request)
    {
        $role = auth()->user()->role;
        
        switch ($role) {
            case 'admin':
                return $this->getAdminStats();
            case 'manager':
                return $this->getManagerStats();
            case 'coach':
                return $this->getCoachStats();
            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Rôle non autorisé',
                ], 403);
        }
    }
}