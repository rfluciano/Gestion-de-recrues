<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Récupère les notifications pour un utilisateur spécifique.
     */
    public function index($id_user)
    {
        // Fetch notifications for the specific user
        $notifications = Notification::where('id_user', $id_user)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json($notifications);
    }


    public function getemall(Request $request)
    {
        $notifications = Notification::all();
        return response()->json($notifications);
    }

        /**
     * Marque une notification comme lue.
     */
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['is_read' => true]);
        event(new MyEvent('Notification', 'modified'));
        return response()->json(['message' => 'Notification marquée comme lue.']);
    }

    /**
     * Marque toutes les notifications comme lues.
     */
    public function markAllAsRead()
    {
        Notification::where('is_read', false)->update(['is_read' => true]);

        return response()->json(['message' => 'Toutes les notifications ont été marquées comme lues.']);
    }


    /**
     * Crée une notification.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_user' => 'nullable|string|exists:useraccount,matricule',
            'event_type' => 'required|string|max:255',
            'message' => 'required|string',
            'data' => 'nullable|array',
        ]);

        $notification = Notification::create([
            'id_user' => $validated['id_user'],
            'event_type' => $validated['event_type'],
            'message' => $validated['message'],
            'data' => json_encode($validated['data']),
        ]);

        return response()->json($notification, 201);
    }

    /**
     * Supprime une notification.
     */
    public function destroy($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'Notification supprimée.']);
    }
}
