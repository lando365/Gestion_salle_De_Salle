<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Notification::where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id);
        
        // Filtrer par état de lecture
        if ($request->has('read')) {
            $query->where('read', $request->boolean('read'));
        }
        
        // Tri
        $query->orderBy('created_at', 'desc');
        
        // Pagination
        $perPage = $request->input('per_page', 10);
        $notifications = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => Notification::where('notifiable_type', get_class($user))
                ->where('notifiable_id', $user->id)
                ->where('read', false)
                ->count(),
        ]);
    }

    public function markAsRead($id)
    {
        $user = auth()->user();
        $notification = Notification::where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->findOrFail($id);
            
        $notification->markAsRead();
        
        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue',
            'data' => $notification,
        ]);
    }

    public function markAllAsRead()
    {
        $user = auth()->user();
        Notification::where('notifiable_type', get_class($user))
            ->where('notifiable_id', $user->id)
            ->where('read', false)
            ->update([
                'read' => true,
                'read_at' => now(),
            ]);
            
        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications ont été marquées comme lues',
        ]);
    }
}