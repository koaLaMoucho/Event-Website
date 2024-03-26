<?php

namespace App\Http\Controllers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use App\Models\Notification;
use Illuminate\Http\Request; 


use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function getNotifications(Request $request)
    {

        $notifications = Notification::with(['event', 'report.comment.event'])
            ->where('notified_user', auth()->id())
            ->latest('timestamp')
            ->paginate(10000);

        $transformedNotifications = $notifications->map(function ($notification) {
            $eventName = null;
        
            if ($notification->notification_type === 'Event' || $notification->notification_type === 'Comment') {
                $eventName = $notification->event ? $notification->event->name : null;
                $event_notificationId = $notification->event_id;
            } elseif ($notification->notification_type === 'Report') {
                $eventName = $notification->report->comment->event->name;
                $event_notificationId = $notification->report->comment->event->event_id;
            }
        
            return [
                'id' => $notification->notification_id,
                'event_id' => $event_notificationId,
                'event_name' => $eventName,
                'notification_type' => $notification->notification_type,
                'timestamp' => $notification->timestamp,
                'viewed' => $notification->viewed,
            ];
        });
            

        return response()->json([
            'notifications' => $transformedNotifications,
        ]);
    }


    public function markAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return redirect()->route('notifications.index');
    }

    public function dismissNotification($notificationId)
    {
        try {
            $notification = Notification::findOrFail($notificationId);
            $notification->viewed = true;
            $notification->save();

            return response()->json(['message' => 'Notification dismissed successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateNotifications(Request $request)
    {

        $unreadCount = auth()->user()->notifications()->where('viewed', false)->count();

        return response()->json(['count' => $unreadCount]);
    }
}
