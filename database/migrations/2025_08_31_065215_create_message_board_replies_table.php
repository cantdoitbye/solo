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
        Schema::create('message_board_replies', function (Blueprint $table) {
             $table->id();
            $table->foreignId('post_id')->constrained('message_board_posts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('parent_reply_id')->nullable()->constrained('message_board_replies')->onDelete('cascade'); // For nested replies
            $table->text('content');
            $table->boolean('is_active')->default(true);
            $table->integer('likes_count')->default(0);
            $table->timestamps();

            // Indexes for performance
            $table->index(['post_id', 'is_active', 'created_at']);
            $table->index(['user_id', 'is_active']);
            $table->index(['parent_reply_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_board_replies');
    }
};
