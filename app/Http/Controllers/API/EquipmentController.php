<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Equipment::query();
        
        // Filtres
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }
        
        if ($request->has('maintenance_due')) {
            $query->where('next_maintenance_date', '<=', now());
        }
        
        // Tri
        $sortField = $request->input('sort_field', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $equipments = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $equipments,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:available,in_use,maintenance,out_of_service',
            'last_maintenance_date' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date|after:last_maintenance_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $equipment = Equipment::create([
            'name' => $request->name,
            'description' => $request->description,
            'purchase_date' => $request->purchase_date,
            'purchase_price' => $request->purchase_price,
            'status' => $request->status ?? 'available',
            'last_maintenance_date' => $request->last_maintenance_date,
            'next_maintenance_date' => $request->next_maintenance_date,
        ]);
        
        // Logging
        ActivityLog::log('created', $equipment);

        return response()->json([
            'success' => true,
            'message' => 'Équipement créé avec succès',
            'data' => $equipment,
        ], 201);
    }

    public function show($id)
    {
        $equipment = Equipment::with('reservations')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $equipment,
        ]);
    }

    public function update(Request $request, $id)
    {
        $equipment = Equipment::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'purchase_date' => 'nullable|date',
            'purchase_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:available,in_use,maintenance,out_of_service',
            'last_maintenance_date' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date|after:last_maintenance_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $oldStatus = $equipment->status;
        
        // Si l'état change de "maintenance" à "available", mettre à jour les dates de maintenance
        if ($oldStatus === 'maintenance' && $request->status === 'available') {
            $request->merge([
                'last_maintenance_date' => now(),
                'next_maintenance_date' => now()->addMonths(3), // Par exemple, la prochaine maintenance dans 3 mois
            ]);
        }
        
        $equipment->update($request->all());
        
        // Logging
        ActivityLog::log('updated', $equipment);

        return response()->json([
            'success' => true,
            'message' => 'Équipement mis à jour avec succès',
            'data' => $equipment,
        ]);
    }

    public function destroy($id)
    {
        $equipment = Equipment::findOrFail($id);
        
        // Vérifier si l'équipement est utilisé dans des réservations actives
        $hasActiveReservations = $equipment->reservations()
            ->where('start_time', '>=', now())
            ->where('status', 'scheduled')
            ->exists();
            
        if ($hasActiveReservations) {
            return response()->json([
                'success' => false,
                'message' => 'Cet équipement ne peut pas être supprimé car il est utilisé dans des réservations à venir.',
            ], 422);
        }
        
        // Soft delete
        $equipment->delete();
        
        // Logging
        ActivityLog::log('deleted', $equipment);

        return response()->json([
            'success' => true,
            'message' => 'Équipement supprimé avec succès',
        ]);
    }

    public function getAvailableEquipments()
    {
        $availableEquipments = Equipment::where('status', 'available')
            ->orderBy('name')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $availableEquipments,
        ]);
    }

    public function getEquipmentsForMaintenance()
    {
        $maintenanceEquipments = Equipment::where(function($query) {
                $query->where('status', 'maintenance')
                      ->orWhere('next_maintenance_date', '<=', now());
            })
            ->orderBy('next_maintenance_date')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $maintenanceEquipments,
        ]);
    }
}