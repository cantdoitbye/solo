<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MessageBoardPost;
use App\Models\MessageBoardReply;
use App\Models\MessageBoardLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class MessageBoardController extends Controller
{
    /**
     * Get all posts with pagination
     */
    public function getPosts(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|in:question,suggestion,general',
            'tags' => 'nullable|array',
            'per_page' => 'nullable|integer|min:1|max:50',
            'search' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $perPage = $request->input('per_page', 20);
        $userId = $request->user()->id;

        $query = MessageBoardPost::with([
            'user:id,phone_number', // Adjust based on your User model fields
            'replies' => function ($q) {
                $q->with('user:id,phone_number')->limit(3); // Show first 3 replies
            }
        ])
        ->active()
        ->orderByActivity();

        // Filter by type
        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        // Filter by tags
        if ($request->has('tags') && is_array($request->tags)) {
            $query->where(function ($q) use ($request) {
                foreach ($request->tags as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('content', 'LIKE', "%{$search}%");
            });
        }

        $posts = $query->paginate($perPage);

        // Add user interaction data
        $posts->getCollection()->transform(function ($post) use ($userId) {
            $post->is_liked_by_user = $post->isLikedBy($userId);
            $post->has_more_replies = $post->replies_count > 3;
            return $post;
        });

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }

    /**
     * Create a new post
     */
    public function createPost(Request $request): JsonResponse
    {
        
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string|max:2000',
            'type' => 'required|in:question,suggestion,general',
            'tags' => 'nullable|array|max:5',
            'tags.*' => 'string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $post = MessageBoardPost::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'content' => $request->content,
            'type' => $request->type,
            'tags' => $request->tags ?? [],
            'last_activity_at' => now()
        ]);

        $post->load('user:id,phone_number');

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully',
            'data' => $post
        ], 201);
    }

    /**
     * Get a single post with all replies
     */
    public function getPost(Request $request, $postId): JsonResponse
    {
        $userId = $request->user()->id;

        $post = MessageBoardPost::with([
            'user:id,phone_number',
            'directReplies' => function ($query) {
                $query->with([
                    'user:id,phone_number',
                    'childReplies' => function ($q) {
                        $q->with('user:id,phone_number');
                    }
                ]);
            }
        ])
        ->active()
        ->find($postId);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        // Increment view count
        $post->incrementViews();

        // Add user interaction data
        $post->is_liked_by_user = $post->isLikedBy($userId);

        // Add like data to replies
        $post->directReplies->each(function ($reply) use ($userId) {
            $reply->is_liked_by_user = $reply->isLikedBy($userId);
            $reply->childReplies->each(function ($childReply) use ($userId) {
                $childReply->is_liked_by_user = $childReply->isLikedBy($userId);
            });
        });

        return response()->json([
            'success' => true,
            'data' => $post
        ]);
    }

    /**
     * Reply to a post or reply
     */
    public function createReply(Request $request, $postId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
            'parent_reply_id' => 'nullable|exists:message_board_replies,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $post = MessageBoardPost::active()->find($postId);
        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        // If replying to a reply, verify it belongs to the same post
        if ($request->parent_reply_id) {
            $parentReply = MessageBoardReply::where('id', $request->parent_reply_id)
                                          ->where('post_id', $postId)
                                          ->active()
                                          ->first();
            if (!$parentReply) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent reply not found or invalid'
                ], 404);
            }
        }

        $reply = MessageBoardReply::create([
            'post_id' => $postId,
            'user_id' => $request->user()->id,
            'parent_reply_id' => $request->parent_reply_id,
            'content' => $request->content
        ]);

        $reply->load('user:id,phone_number');

        return response()->json([
            'success' => true,
            'message' => 'Reply created successfully',
            'data' => $reply
        ], 201);
    }

    /**
     * Like/unlike a post or reply
     */
    public function toggleLike(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:post,reply',
            'id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->user()->id;
        $type = $request->type;
        $id = $request->id;

        // Get the model
        if ($type === 'post') {
            $model = MessageBoardPost::active()->find($id);
            $likeableType = MessageBoardPost::class;
        } else {
            $model = MessageBoardReply::active()->find($id);
            $likeableType = MessageBoardReply::class;
        }

        if (!$model) {
            return response()->json([
                'success' => false,
                'message' => ucfirst($type) . ' not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $existingLike = MessageBoardLike::where([
                'user_id' => $userId,
                'likeable_type' => $likeableType,
                'likeable_id' => $id
            ])->first();

            if ($existingLike) {
                // Unlike
                $existingLike->delete();
                $action = 'unliked';
            } else {
                // Like
                MessageBoardLike::create([
                    'user_id' => $userId,
                    'likeable_type' => $likeableType,
                    'likeable_id' => $id
                ]);
                $action = 'liked';
            }

            // Refresh model to get updated likes count
            $model->refresh();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => ucfirst($type) . ' ' . $action . ' successfully',
                'data' => [
                    'likes_count' => $model->likes_count,
                    'is_liked' => $action === 'liked'
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update like status'
            ], 500);
        }
    }

    /**
     * Update a post (only by the creator)
     */
    public function updatePost(Request $request, $postId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string|max:2000',
            'tags' => 'nullable|array|max:5',
            'tags.*' => 'string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $post = MessageBoardPost::active()->find($postId);
        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        // Check if user owns the post
        if ($post->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit your own posts'
            ], 403);
        }

        $post->update($request->only(['title', 'content', 'tags']));
        $post->load('user:id,phone_number');

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully',
            'data' => $post
        ]);
    }

    /**
     * Delete a post (only by the creator)
     */
    public function deletePost(Request $request, $postId): JsonResponse
    {
        $post = MessageBoardPost::active()->find($postId);
        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found'
            ], 404);
        }

        // Check if user owns the post
        if ($post->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own posts'
            ], 403);
        }

        $post->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully'
        ]);
    }

    /**
     * Get available tags
     */
    public function getTags(): JsonResponse
    {
        $tags = MessageBoardPost::active()
                               ->whereNotNull('tags')
                               ->pluck('tags')
                               ->flatten()
                               ->unique()
                               ->values();

        return response()->json([
            'success' => true,
            'data' => $tags
        ]);
    }
}