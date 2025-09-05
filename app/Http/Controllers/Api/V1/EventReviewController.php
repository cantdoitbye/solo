<?php
// app/Http/Controllers/Api/V1/EventReviewController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\EventReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventReviewController extends Controller
{
    /**
     * Submit a review for an event
     * POST /api/v1/events/{eventId}/reviews
     */
    public function submitReview(Request $request, int $eventId): JsonResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string|max:1000',
            'is_anonymous' => 'nullable|boolean'
        ]);

        try {
            $userId = $request->user()->id;

            // Check if event exists
            $event = Event::find($eventId);
            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found'
                ], 404);
            }

            // Check if user attended the event
            $attendance = EventAttendee::where('event_id', $eventId)
                ->where('user_id', $userId)
                ->whereIn('status', ['confirmed', 'interested'])
                ->first();

            if (!$attendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only review events you have attended'
                ], 403);
            }

            // Check if event has already passed
            // event_date is a Carbon date, event_time is a Carbon datetime with H:i format
            $eventDate = $event->event_date; // Carbon date
            $eventTimeString = $event->event_time->format('H:i:s'); // Get time as string
            
            // Create full datetime by combining date and time
            $eventDateTime = $eventDate->copy()->setTimeFromTimeString($eventTimeString);
            
            if (now()->lt($eventDateTime)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only review events that have already occurred'
                ], 400);
            }

            // Check if user has already reviewed this event
            $existingReview = EventReview::where('event_id', $eventId)
                ->where('user_id', $userId)
                ->first();

            if ($existingReview) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already submitted a review for this event'
                ], 400);
            }

            // Create the review
            $review = EventReview::create([
                'event_id' => $eventId,
                'user_id' => $userId,
                'rating' => $request->rating,
                'review_text' => $request->review_text,
                'is_anonymous' => $request->boolean('is_anonymous', false)
            ]);
            app(\App\Services\FirebaseNotificationService::class)->sendEventReviewNotification(
    $eventId,
    $userId,
    $request->input('rating')
);

            return response()->json([
                'success' => true,
                'message' => 'Review submitted successfully',
                'data' => [
                    'review_id' => $review->id,
                    'rating' => $review->rating,
                    'review_text' => $review->review_text,
                    'is_anonymous' => $review->is_anonymous,
                    'submitted_at' => $review->created_at->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit review: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reviews for a specific event
     * GET /api/v1/events/{eventId}/reviews
     */
    public function getEventReviews(Request $request, int $eventId): JsonResponse
    {
        try {
            $event = Event::find($eventId);
            if (!$event) {
                return response()->json([
                    'success' => false,
                    'message' => 'Event not found'
                ], 404);
            }

            $reviews = EventReview::forEvent($eventId)
                ->with(['user:id,name'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'rating' => $review->rating,
                        'review_text' => $review->review_text,
                        'reviewer_name' => $review->is_anonymous ? 'Anonymous' : $review->user->name,
                        'is_anonymous' => $review->is_anonymous,
                        'submitted_at' => $review->created_at->format('Y-m-d H:i:s')
                    ];
                });

            // Calculate review statistics
            $totalReviews = $reviews->count();
            $averageRating = $totalReviews > 0 ? round($reviews->avg('rating'), 1) : 0;
            $ratingCounts = [
                5 => $reviews->where('rating', 5)->count(),
                4 => $reviews->where('rating', 4)->count(),
                3 => $reviews->where('rating', 3)->count(),
                2 => $reviews->where('rating', 2)->count(),
                1 => $reviews->where('rating', 1)->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'event' => [
                        'id' => $event->id,
                        'name' => $event->name
                    ],
                    'statistics' => [
                        'total_reviews' => $totalReviews,
                        'average_rating' => $averageRating,
                        'rating_distribution' => $ratingCounts
                    ],
                    'reviews' => $reviews
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reviews: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user's review for an event
     * PUT /api/v1/events/{eventId}/reviews
     */
    public function updateReview(Request $request, int $eventId): JsonResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string|max:1000',
            'is_anonymous' => 'nullable|boolean'
        ]);

        try {
            $userId = $request->user()->id;

            $review = EventReview::where('event_id', $eventId)
                ->where('user_id', $userId)
                ->first();

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review not found'
                ], 404);
            }

            $review->update([
                'rating' => $request->rating,
                'review_text' => $request->review_text,
                'is_anonymous' => $request->boolean('is_anonymous', false)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Review updated successfully',
                'data' => [
                    'review_id' => $review->id,
                    'rating' => $review->rating,
                    'review_text' => $review->review_text,
                    'is_anonymous' => $review->is_anonymous,
                    'updated_at' => $review->updated_at->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update review: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user's review for an event
     * DELETE /api/v1/events/{eventId}/reviews
     */
    public function deleteReview(Request $request, int $eventId): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            $review = EventReview::where('event_id', $eventId)
                ->where('user_id', $userId)
                ->first();

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'Review not found'
                ], 404);
            }

            $review->delete();

            return response()->json([
                'success' => true,
                'message' => 'Review deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete review: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's review for a specific event
     * GET /api/v1/events/{eventId}/my-review
     */
    public function getMyReview(Request $request, int $eventId): JsonResponse
    {
        try {
            $userId = $request->user()->id;

            $review = EventReview::where('event_id', $eventId)
                ->where('user_id', $userId)
                ->first();

            if (!$review) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have not reviewed this event yet'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'review_id' => $review->id,
                    'rating' => $review->rating,
                    'review_text' => $review->review_text,
                    'is_anonymous' => $review->is_anonymous,
                    'submitted_at' => $review->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $review->updated_at->format('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch review: ' . $e->getMessage()
            ], 500);
        }
    }
}