<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Reservation;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        return response()->json([
            'stats' => [
                'active_members' => Member::where('status', 'active')->count(),
                'today_reservations' => Reservation::where('date', $today)->count(),
                'monthly_revenue' => Payment::whereMonth('payment_date', $today->month)
                    ->whereYear('payment_date', $today->year)
                    ->sum('amount'),
                'expiring_soon' => Member::where('subscription_end', '>=', $today)
                    ->where('subscription_end', '<=', $today->addDays(7))
                    ->count(),
            ],
            'today_reservations' => Reservation::with(['member', 'service'])
                ->where('date', $today)
                ->orderBy('start_time')
                ->get(),
        ]);
    }
}