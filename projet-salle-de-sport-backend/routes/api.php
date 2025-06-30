<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\MemberController;
use App\Http\Controllers\API\ReservationController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\DashboardController;


Route::get('/reservations/today', [ReservationController::class, 'today']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    
    // Membres
    Route::apiResource('members', MemberController::class);
    
    // Réservations
    Route::apiResource('reservations', ReservationController::class);


    
    // Services
    Route::apiResource('services', ServiceController::class);
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/payments', function () {
    return \App\Models\Payment::with('member')
        ->orderBy('payment_date', 'desc')
        ->get()
        ->map(function ($payment) {
            return [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'payment_date' => $payment->payment_date->format('Y-m-d'), // Format standard
                'payment_method' => $payment->payment_method,
                'member' => $payment->member
            ];
        });
});
});

    

