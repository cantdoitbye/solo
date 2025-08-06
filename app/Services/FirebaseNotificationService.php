<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\NotificationLog;
use App\Models\User;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class FirebaseNotificationService
{
    private string $fcmEndpoint;

    public function __construct()
    {
        $this->fcmEndpoint = config('services.firebase.fcm_endpoint', 'https://fcm.googleapis.com/v1/projects/' . config('services.firebase.project_id') . '/messages:send');
    }

    /**
     * Generate access token for Firebase Cloud Messaging using service account
     */
    private function generateAccessToken(): ?string
    {
        // Check if the token exists in cache
        if (Cache::has('firebase_access_token')) {
            return Cache::get('firebase_access_token');
        }

        try {
            // Path to the service_account.json file
            $credentialsFilePath = storage_path('app/private/service_account.json');
            
            if (!file_exists($credentialsFilePath)) {
                Log::error('Firebase service account file not found', ['path' => $credentialsFilePath]);
                return null;
            }

            // Create credentials object
            $credentials = new ServiceAccountCredentials(
                ['https://www.googleapis.com/auth/firebase.messaging'],
                $credentialsFilePath
            );

            // Fetch the token
            $token = $credentials->fetchAuthToken();
            $accessToken = $token['access_token'];

            // Cache the token for 55 minutes (Firebase tokens expire in 1 hour)
            Cache::put('firebase_access_token', $accessToken, now()->addMinutes(55));

            return $accessToken;
        } catch (\Exception $e) {
            Log::error('Error generating Firebase access token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Main method to send push notification to a single user
     */
    public function sendPushNotification($to, string $title, string $body, array $data = [], ?string $type = null): bool
    {
        $accessToken = $this->generateAccessToken();
        
        if (!$accessToken) {
            Log::error('Failed to generate Firebase access token');
            return false;
        }

        // Handle both User model and direct FCM token
        $fcmToken = is_string($to) ? $to : $to->fcm_token;
        $userId = is_string($to) ? null : $to->id;

        if (empty($fcmToken)) {
            Log::warning('FCM token not found', ['user_id' => $userId]);
            return false;
        }

        // Create notification record if we have user ID
        $notification = null;
        if ($userId) {
            $notification = AppNotification::create([
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'data' => $data,
                'sent_to_users' => [$userId],
                'total_sent' => 1,
                'sent_at' => now(),
            ]);
        }

        try {
            $message = [
                'message' => [
                    'token' => $fcmToken,
                    'notification' => [
                        'title' => $title,
                        'body' => $body
                    ],
                    'data' => array_merge($data, [
                        'notification_id' => $notification ? (string)$notification->id : '',
                        'type' => $type ?? 'general',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ])
                ]
            ];

            Log::info('Sending FCM payload', ['payload' => $message, 'user_id' => $userId]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($this->fcmEndpoint, $message);

            // Handle response
            if ($response->status() == 200) {
                Log::info('Notification sent successfully', ['user_id' => $userId, 'response' => $response->body()]);
                
                if ($notification) {
                    $this->createLog($notification, $userId, $fcmToken, 'sent', null, $response->json());
                    $notification->update(['total_delivered' => 1]);
                }
                
                return true;
            } else if ($response->status() == 404) {
                Log::warning('FCM token unregistered or invalid', ['token' => $fcmToken, 'response' => $response->body()]);
                
                if ($userId) {
                    $this->invalidateUserToken($userId);
                    if ($notification) {
                        $this->createLog($notification, $userId, $fcmToken, 'failed', 'Token unregistered', $response->json());
                        $notification->update(['total_failed' => 1]);
                    }
                }
                
                return false;
            } else {
                Log::error('Error sending FCM notification', ['status' => $response->status(), 'response' => $response->body()]);
                
                if ($notification) {
                    $this->createLog($notification, $userId, $fcmToken, 'failed', 'HTTP Error: ' . $response->status(), $response->json());
                    $notification->update(['total_failed' => 1]);
                }
                
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception while sending FCM notification: ' . $e->getMessage());
            
            if ($notification) {
                $this->createLog($notification, $userId, $fcmToken, 'failed', $e->getMessage());
                $notification->update(['total_failed' => 1]);
            }
            
            return false;
        }
    }

    /**
     * Send bulk notifications (batch processing)
     */
    public function sendBulkNotifications(array $recipients, string $title, string $body, array $data = [], ?string $type = null): AppNotification
    {
        $notification = AppNotification::create([
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'data' => $data,
            'sent_to_users' => array_keys($recipients), // [user_id => fcm_token]
            'sent_at' => now(),
        ]);

        $successCount = 0;
        $failureCount = 0;

        foreach ($recipients as $userId => $fcmToken) {
            $success = $this->sendPushNotification($fcmToken, $title, $body, array_merge($data, [
                'notification_id' => (string)$notification->id
            ]), $type);

            if ($success) {
                $successCount++;
            } else {
                $failureCount++;
            }

            // Add small delay to avoid rate limiting
            usleep(100000); // 0.1 seconds
        }

        $notification->update([
            'total_sent' => count($recipients),
            'total_delivered' => $successCount,
            'total_failed' => $failureCount,
        ]);

        return $notification;
    }

    /**
     * Send notification to all users
     */
    public function sendToAllUsers(string $title, string $body, ?string $type = null, ?array $data = null): AppNotification
    {
        $recipients = User::whereNotNull('fcm_token')->pluck('fcm_token', 'id')->toArray();
        return $this->sendBulkNotifications($recipients, $title, $body, $data ?? [], $type);
    }

    /**
     * Send notification to specific users
     */
    public function sendToUsers(string $title, string $body, Collection $userIds, ?string $type = null, ?array $data = null): AppNotification
    {
        $recipients = User::whereIn('id', $userIds)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token', 'id')
            ->toArray();

        return $this->sendBulkNotifications($recipients, $title, $body, $data ?? [], $type);
    }

    /**
     * Send notification to single user
     */
    public function sendToUser(string $title, string $body, int $userId, ?string $type = null, ?array $data = null): bool
    {
        $user = User::find($userId);
        if (!$user || !$user->fcm_token) {
            Log::warning('User not found or FCM token missing', ['user_id' => $userId]);
            return false;
        }

        return $this->sendPushNotification($user, $title, $body, $data ?? [], $type);
    }

    /**
     * Send notification to event attendees
     */
    public function sendToEventAttendees(string $title, string $body, int $eventId, ?array $data = null): AppNotification
    {
        $userIds = \DB::table('event_attendees')
            ->where('event_id', $eventId)
            ->whereIn('status', ['interested', 'confirmed'])
            ->pluck('user_id');

        return $this->sendToUsers($title, $body, $userIds, 'event', array_merge($data ?? [], [
            'event_id' => (string)$eventId
        ]));
    }

    /**
     * Send visitor notification (similar to your example)
     */
    public function sendVisitorNotification($to, $visitorData): bool
    {
        $title = "🏠 New Visitor Request";
        $body = "Visitor {$visitorData['visitor_name']} wants to visit you";

        $data = [
            'notification_type' => 'visitor_request',
            'visitor_name' => $visitorData['visitor_name'],
            'visitor_number' => $visitorData['visitor_number'] ?? '',
            'reason' => $visitorData['reason'] ?? '',
        ];

        return $this->sendPushNotification($to, $title, $body, $data, 'visitor');
    }

    /**
     * Send welcome notification
     */
    public function sendWelcomeNotification(int $userId): bool
    {
        return $this->sendToUser(
            'Welcome to Solo! 🎉',
            'Your journey starts here. Discover amazing events and connect with new people!',
            $userId,
            'welcome',
            ['onboarding_completed' => true]
        );
    }

    /**
     * Send event reminder notification
     */
    public function sendEventReminder(int $eventId, string $eventTitle, \DateTime $eventDateTime): AppNotification
    {
        $timeUntil = $eventDateTime->diff(now());
        $timeString = $timeUntil->h > 0 ? $timeUntil->h . ' hours' : $timeUntil->i . ' minutes';

        return $this->sendToEventAttendees(
            "Event Starting Soon! ⏰",
            "{$eventTitle} starts in {$timeString}. Get ready!",
            $eventId,
            [
                'event_id' => (string)$eventId,
                'time_until_event' => $timeString,
                'event_datetime' => $eventDateTime->format('c')
            ]
        );
    }

    /**
     * Update user FCM token
     */
    public function updateUserToken(int $userId, string $fcmToken): bool
    {
        return User::where('id', $userId)->update([
            'fcm_token' => $fcmToken,
            'fcm_token_updated_at' => now(),
        ]);
    }

    /**
     * Invalidate user's FCM token
     */
    private function invalidateUserToken(int $userId): void
    {
        User::where('id', $userId)->update([
            'fcm_token' => null,
            'fcm_token_updated_at' => null,
        ]);

        Log::info('Invalidated FCM token for user', ['user_id' => $userId]);
    }

    /**
     * Create notification log entry
     */
    private function createLog(AppNotification $notification, int $userId, string $token, string $status, ?string $error = null, ?array $fcmResponse = null): void
    {
        NotificationLog::create([
            'app_notification_id' => $notification->id,
            'user_id' => $userId,
            'fcm_token' => $token,
            'status' => $status,
            'error_message' => $error,
            'fcm_response' => $fcmResponse,
            'sent_at' => now(),
        ]);
    }

    /**
     * Test notification for development
     */
    public function sendTestNotification(int $userId): bool
    {
        return $this->sendToUser(
            'Test Notification 🧪',
            'This is a test notification from your app!',
            $userId,
            'test',
            ['test' => true, 'timestamp' => now()->toISOString()]
        );
    }

    /**
     * Send SOS notification (similar to your emergency example)
     */
    public function sendSOSNotification($sender, array $recipients = []): bool
    {
        $title = "🚨 URGENT SOS ALERT!";
        $body = "⚠️ {$sender->name} IS IN DANGER! Please react urgently!";

        $data = [
            'notification_type' => 'sos',
            'sender_id' => (string)$sender->id,
            'sender_name' => $sender->name,
            'timestamp' => now()->toISOString(),
        ];

        // If no specific recipients, send to all users in the same area/group
        if (empty($recipients)) {
            // You can customize this logic based on your app's structure
            $recipients = User::where('id', '!=', $sender->id)
                ->whereNotNull('fcm_token')
                ->get();
        }

        $successCount = 0;
        foreach ($recipients as $recipient) {
            if ($this->sendPushNotification($recipient, $title, $body, $data, 'sos')) {
                $successCount++;
            }
        }

        Log::info('SOS notification sent', [
            'sender_id' => $sender->id,
            'total_recipients' => count($recipients),
            'successful_sends' => $successCount
        ]);

        return $successCount > 0;
    }
}