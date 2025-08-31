<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('message_board_posts', function (Blueprint $table) {
             $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->enum('type', ['question', 'suggestion', 'general'])->default('question');
            $table->json('tags')->nullable(); // e.g., ['restaurant', 'events', 'activities']
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('likes_count')->default(0);
            $table->integer('replies_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'is_active']);
            $table->index(['type', 'is_active']);
            $table->index(['is_pinned', 'created_at']);
            $table->index(['last_activity_at']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_board_posts');
    }
};
