<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller\API;
use App\Models\Service;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::query();
        
        // Filtres
        if ($request->has('active')) {
            $query->where('active', $request->boolean('active'));
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }
        
        // Tri
        $sortField = $request->input('sort_field', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $services = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'capacity' => 'required|integer|min:1',
            'active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $service = Service::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'duration' => $request->duration,
            'capacity' => $request->capacity,
            'active' => $request->active ?? true,
        ]);
        
        // Logging
        ActivityLog::log('created', $service);

        return response()->json([
            'success' => true,
            'message' => 'Service créé avec succès',
            'data' => $service,
        ], 201);
    }

    public function show($id)
    {
        $service = Service::with('reservations')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $service,
        ]);
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'duration' => 'sometimes|required|integer|min:1',
            'capacity' => 'sometimes|required|integer|min:1',
            'active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $service->update($request->all());
        
        // Logging
        ActivityLog::log('updated', $service);

        return response()->json([
            'success' => true,
            'message' => 'Service mis à jour avec succès',
            'data' => $service,
        ]);
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        
        // Vérifier si le service est utilisé dans des réservations actives
        $hasActiveReservations = $service->reservations()
            ->where('start_time', '>=', now())
            ->where('status', 'scheduled')
            ->exists();
            
        if ($hasActiveReservations) {
            return response()->json([
                'success' => false,
                'message' => 'Ce service ne peut pas être supprimé car il est utilisé dans des réservations à venir.',
            ], 422);
        }
        
        // Soft delete
        $service->delete();
        
        // Logging
        ActivityLog::log('deleted', $service);

        return response()->json([
            'success' => true,
            'message' => 'Service supprimé avec succès',
        ]);
    }

    public function getActiveServices()
    {
        $activeServices = Service::where('active', true)
            ->orderBy('name')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $activeServices,
        ]);
    }
}