<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\Service;
use Carbon\Carbon;

class ReservationService
{
    public function checkAvailability($serviceId, $date, $startTime, $endTime, $excludeId = null)
    {
        $service = Service::findOrFail($serviceId);
        
        $conflictingReservations = Reservation::where('service_id', $serviceId)
            ->where('date', $date)
            ->where(function($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                      ->orWhereBetween('end_time', [$startTime, $endTime])
                      ->orWhere(function($q) use ($startTime, $endTime) {
                          $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                      });
            })
            ->where('status', 'confirmed');

        if ($excludeId) {
            $conflictingReservations->where('id', '!=', $excludeId);
        }

        return $conflictingReservations->count() === 0;
    }

    public function createReservation(array $data)
    {
        if (!$this->checkAvailability(
            $data['service_id'], 
            $data['date'], 
            $data['start_time'], 
            $data['end_time']
        )) {
            throw new \Exception('The selected time slot is not available');
        }

        return Reservation::create($data);
    }
}