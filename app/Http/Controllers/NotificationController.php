<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Display a listing of the user's notifications.
     */
    public function index()
    {
        $notifications = Auth::user()->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Get recent notifications for the navbar dropdown.
     */
    public function getRecent()
    {
        $notifications = Auth::user()->notifications()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json($notifications);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Get the count of unread notifications.
     */
    public function getUnreadCount()
    {
        $count = Auth::user()->unreadNotifications()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Delete a notification.
     */
    public function destroy($id)
    {
        $notification = Auth::user()->notifications()->find($id);

        if ($notification) {
            $notification->delete();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Clear all notifications.
     */
    public function clear()
    {
        Auth::user()->notifications()->delete();

        return response()->json(['success' => true]);
    }
}
