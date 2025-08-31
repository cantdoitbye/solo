<?php
// app/Services/MessageBoardService.php

namespace App\Services;

use App\Models\MessageBoardPost;
use App\Models\MessageBoardReply;
use App\Models\MessageBoardLike;
use Illuminate\Support\Facades\DB;

class MessageBoardService
{
    /**
     * Get popular posts based on likes and replies
     */
    public function getPopularPosts(int $limit = 10)
    {
        return MessageBoardPost::active()
            ->select('*')
            ->selectRaw('(likes_count * 2 + replies_count + views_count) as popularity_score')
            ->with(['user:id,phone_number'])
            ->orderByDesc('popularity_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Get trending posts (recent activity)
     */
    public function getTrendingPosts(int $limit = 10)
    {
        return MessageBoardPost::active()
            ->where('last_activity_at', '>=', now()->subDays(7))
            ->orderByActivity()
            ->limit($limit)
            ->get();
    }

    /**
     * Get user's posts
     */
    public function getUserPosts(int $userId, int $perPage = 20)
    {
        return MessageBoardPost::with(['user:id,phone_number'])
            ->where('user_id', $userId)
            ->active()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get user's replies
     */
    public function getUserReplies(int $userId, int $perPage = 20)
    {
        return MessageBoardReply::with([
            'user:id,phone_number',
            'post:id,title,user_id'
        ])
        ->where('user_id', $userId)
        ->active()
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
    }

    /**
     * Search posts and replies
     */
    public function searchContent(string $query, array $filters = [])
    {
        $posts = MessageBoardPost::active()
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('content', 'LIKE', "%{$query}%");
            });

        // Apply filters
        if (isset($filters['type'])) {
            $posts->where('type', $filters['type']);
        }

        if (isset($filters['tags']) && is_array($filters['tags'])) {
            $posts->where(function ($q) use ($filters) {
                foreach ($filters['tags'] as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        if (isset($filters['user_id'])) {
            $posts->where('user_id', $filters['user_id']);
        }

        return $posts->orderByActivity()->get();
    }

    /**
     * Get post statistics
     */
    public function getPostStatistics(int $postId): array
    {
        $post = MessageBoardPost::find($postId);
        if (!$post) {
            return [];
        }

        return [
            'total_likes' => $post->likes_count,
            'total_replies' => $post->replies_count,
            'total_views' => $post->views_count,
            'unique_participants' => MessageBoardReply::where('post_id', $postId)
                                                    ->distinct('user_id')
                                                    ->count('user_id'),
            'last_activity' => $post->last_activity_at,
            'engagement_rate' => $post->views_count > 0 
                ? round((($post->likes_count + $post->replies_count) / $post->views_count) * 100, 2)
                : 0
        ];
    }

    /**
     * Get community stats
     */
    public function getCommunityStats(): array
    {
        return [
            'total_posts' => MessageBoardPost::active()->count(),
            'total_replies' => MessageBoardReply::active()->count(),
            'total_likes' => MessageBoardLike::count(),
            'active_users_today' => MessageBoardPost::where('created_at', '>=', now()->startOfDay())
                                                  ->distinct('user_id')
                                                  ->count('user_id'),
            'posts_today' => MessageBoardPost::active()
                                           ->where('created_at', '>=', now()->startOfDay())
                                           ->count(),
            'most_popular_tags' => $this->getMostPopularTags(10)
        ];
    }

    /**
     * Get most popular tags
     */
    public function getMostPopularTags(int $limit = 20): array
    {
        $tags = MessageBoardPost::active()
                               ->whereNotNull('tags')
                               ->pluck('tags')
                               ->flatten()
                               ->countBy()
                               ->sortDesc()
                               ->take($limit);

        return $tags->map(function ($count, $tag) {
            return [
                'tag' => $tag,
                'count' => $count
            ];
        })->values()->toArray();
    }

    /**
     * Moderate content (admin functionality)
     */
    public function moderatePost(int $postId, string $action): bool
    {
        $post = MessageBoardPost::find($postId);
        if (!$post) {
            return false;
        }

        switch ($action) {
            case 'pin':
                $post->update(['is_pinned' => true]);
                break;
            case 'unpin':
                $post->update(['is_pinned' => false]);
                break;
            case 'deactivate':
                $post->update(['is_active' => false]);
                break;
            case 'activate':
                $post->update(['is_active' => true]);
                break;
            default:
                return false;
        }

        return true;
    }

    /**
     * Get recommended posts for user based on their interests
     */
    public function getRecommendedPosts(int $userId, int $limit = 10)
    {
        // Get user's interests and previous interactions
        $user = \App\Models\User::find($userId);
        if (!$user || empty($user->interests)) {
            return $this->getPopularPosts($limit);
        }

        $userInterests = $user->interests;

        return MessageBoardPost::active()
            ->where('user_id', '!=', $userId) // Exclude user's own posts
            ->where(function ($query) use ($userInterests) {
                foreach ($userInterests as $interest) {
                    $query->orWhereJsonContains('tags', strtolower($interest))
                          ->orWhere('content', 'LIKE', "%{$interest}%")
                          ->orWhere('title', 'LIKE', "%{$interest}%");
                }
            })
            ->orderByActivity()
            ->limit($limit)
            ->get();
    }
}