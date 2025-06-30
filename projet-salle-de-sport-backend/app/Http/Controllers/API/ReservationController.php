<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Http\Requests\ReservationRequest;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $query = Reservation::with(['member', 'service']);

        if ($request->has('date')) {
            $query->where('date', $request->date);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('member_id')) {
            $query->where('member_id', $request->member_id);
        }

        return response()->json($query->orderBy('date')->orderBy('start_time')->paginate(10));
    }

    public function today(Request $request)
    {
        $today = now()->format('Y-m-d');
        
        return Reservation::with(['member', 'service'])
            ->whereDate('date', $today) // Utilisez whereDate pour comparer seulement la partie date
            ->orderBy('start_time')
            ->get();
    }

    public function store(ReservationRequest $request)
    {
        $reservation = Reservation::create($request->validated());
        return response()->json($reservation->load(['member', 'service']), 201);
    }

    public function show(Reservation $reservation)
    {
        return response()->json($reservation->load(['member', 'service']));
    }

    public function update(ReservationRequest $request, Reservation $reservation)
    {
        $reservation->update($request->validated());
        return response()->json($reservation->load(['member', 'service']));
    }

    public function destroy(Reservation $reservation)
    {
        $reservation->delete();
        return response()->json(null, 204);
    }
}