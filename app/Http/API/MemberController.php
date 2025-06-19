<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ActivityLog;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::query();
        
        // Filtres
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Tri
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        // Pagination
        $perPage = $request->input('per_page', 15);
        $members = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $members,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:members,email',
            'phone' => 'required|string|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'emergency_contact' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'nullable|in:active,inactive,pending',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();
        
        // Traitement de la photo si présente
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = time() . '.' . $photo->getClientOriginalExtension();
            $photo->storeAs('public/members', $filename);
            $data['photo'] = 'members/' . $filename;
        }

        $member = Member::create($data);
        
        // Logging
        ActivityLog::log('created', $member);

        return response()->json([
            'success' => true,
            'message' => 'Membre créé avec succès',
            'data' => $member,
        ], 201);
    }

    public function show($id)
    {
        $member = Member::with(['subscriptions', 'reservations', 'payments'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $member,
        ]);
    }

    public function update(Request $request, $id)
    {
        $member = Member::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:members,email,' . $id,
            'phone' => 'sometimes|required|string|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'emergency_contact' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'status' => 'nullable|in:active,inactive,pending',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();
        
        // Traitement de la photo si présente
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = time() . '.' . $photo->getClientOriginalExtension();
            $photo->storeAs('public/members', $filename);
            $data['photo'] = 'members/' . $filename;
        }

        $member->update($data);
        
        // Logging
        ActivityLog::log('updated', $member);

        return response()->json([
            'success' => true,
            'message' => 'Membre mis à jour avec succès',
            'data' => $member,
        ]);
    }

    public function destroy($id)
    {
        $member = Member::findOrFail($id);
        
        // Soft delete
        $member->delete();
        
        // Logging
        ActivityLog::log('deleted', $member);

        return response()->json([
            'success' => true,
            'message' => 'Membre supprimé avec succès',
        ]);
    }

    public function getActiveMembers()
    {
        $activeMembers = Member::where('status', 'active')
            ->whereHas('subscriptions', function($query) {
                $query->where('status', 'active')
                      ->where('end_date', '>=', now());
            })
            ->get();
            
        return response()->json([
            'success' => true,
            'count' => $activeMembers->count(),
            'data' => $activeMembers,
        ]);
    }

    public function getMemberStats()
    {
        $totalMembers = Member::count();
        $activeMembers = Member::where('status', 'active')->count();
        $inactiveMembers = Member::where('status', 'inactive')->count();
        $pendingMembers = Member::where('status', 'pending')->count();
        
        // Nouveaux membres ce mois-ci
        $newMembersThisMonth = Member::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
            
        return response()->json([
            'success' => true,
            'data' => [
                'total' => $totalMembers,
                'active' => $activeMembers,
                'inactive' => $inactiveMembers,
                'pending' => $pendingMembers,
                'new_this_month' => $newMembersThisMonth,
            ],
        ]);
    }
}