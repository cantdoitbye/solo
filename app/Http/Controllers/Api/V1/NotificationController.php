<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get user's notifications with pagination
     * GET /api/v1/notifications
     */
    public function getUserNotifications(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
            'type' => 'nullable|string|in:member_join,event_review,dm_request,welcome,event_reminder,general',
            'status' => 'nullable|string|in:sent,delivered,failed,clicked',
            'unread_only' => 'nullable|boolean'
        ]);

        $userId = $request->user()->id;
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        // Get notification logs for this user with related notification data
        $query = NotificationLog::with(['appNotification'])
            ->where('user_id', $userId)
            ->orderBy('sent_at', 'desc');

        // Filter by notification type
        if ($request->has('type')) {
            $query->whereHas('appNotification', function ($q) use ($request) {
                $q->where('type', $request->input('type'));
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter unread only (not clicked)
        if ($request->boolean('unread_only')) {
            $query->where('status', '!=', 'clicked');
        }

        $notifications = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => collect($notifications->items())->map(function ($log) {
                    $notification = $log->appNotification;
                    return [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'body' => $notification->body,
                        'type' => $notification->type,
                        'data' => $notification->data ?? [],
                        'status' => $log->status,
                        'is_read' => $log->status === 'clicked',
                        'created_at' => $notification->created_at->toISOString(),
                    ];
                })->toArray(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'last_page' => $notifications->lastPage(),
                    'has_more_pages' => $notifications->hasMorePages(),
                    'from' => $notifications->firstItem(),
                    'to' => $notifications->lastItem(),
                ]
            ]
        ]);
    }

    /**
     * Mark notification as read/clicked
     * POST /api/v1/notifications/{notificationId}/mark-read
     */
    public function markNotificationAsRead(Request $request, int $notificationId): JsonResponse
    {
        $userId = $request->user()->id;

        $notificationLog = NotificationLog::where('user_id', $userId)
            ->where('app_notification_id', $notificationId)
            ->first();

        if (!$notificationLog) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notificationLog->markAsClicked();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => [
                'notification_id' => $notificationId,
                'status' => $notificationLog->status,
                'clicked_at' => $notificationLog->clicked_at->toISOString(),
            ]
        ]);
    }

    /**
     * Mark all notifications as read for user
     * POST /api/v1/notifications/mark-all-read
     */
    public function markAllNotificationsAsRead(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $updatedCount = NotificationLog::where('user_id', $userId)
            ->where('status', '!=', 'clicked')
            ->update([
                'status' => 'clicked',
                'clicked_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
            'data' => [
                'updated_count' => $updatedCount
            ]
        ]);
    }

    /**
     * Get notification counts and summary
     * GET /api/v1/notifications/summary
     */
    public function getNotificationSummary(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Get unread count
        $unreadCount = NotificationLog::where('user_id', $userId)
            ->where('status', '!=', 'clicked')
            ->count();

        // Get total count
        $totalCount = NotificationLog::where('user_id', $userId)->count();

        // Get recent notifications (last 7 days)
        $recentCount = NotificationLog::where('user_id', $userId)
            ->where('sent_at', '>=', now()->subDays(7))
            ->count();

        // Get counts by type
        $typeCounts = NotificationLog::where('user_id', $userId)
            ->join('app_notifications', 'notification_logs.app_notification_id', '=', 'app_notifications.id')
            ->selectRaw('app_notifications.type, COUNT(*) as count')
            ->groupBy('app_notifications.type')
            ->pluck('count', 'type')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $unreadCount,
                'total_count' => $totalCount,
                'recent_count' => $recentCount,
                'type_counts' => [
                    'member_join' => $typeCounts['member_join'] ?? 0,
                    'event_review' => $typeCounts['event_review'] ?? 0,
                    'dm_request' => $typeCounts['dm_request'] ?? 0,
                    'welcome' => $typeCounts['welcome'] ?? 0,
                    'event_reminder' => $typeCounts['event_reminder'] ?? 0,
                    'general' => $typeCounts['general'] ?? 0,
                ]
            ]
        ]);
    }

    /**
     * Delete notification
     * DELETE /api/v1/notifications/{notificationId}
     */
    public function deleteNotification(Request $request, int $notificationId): JsonResponse
    {
        $userId = $request->user()->id;

        $notificationLog = NotificationLog::where('user_id', $userId)
            ->where('app_notification_id', $notificationId)
            ->first();

        if (!$notificationLog) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notificationLog->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    }
}