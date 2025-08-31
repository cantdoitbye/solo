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
        Schema::create('event_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('rating')->comment('Rating from 1 to 5 stars');
            $table->text('review_text')->nullable()->comment('Optional text review');
            $table->boolean('is_anonymous')->default(false)->comment('Whether review should be anonymous');
            $table->timestamps();
            
            // Ensure one review per user per event
            $table->unique(['event_id', 'user_id']);
            
            // Indexes
            $table->index(['event_id', 'rating']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_reviews');
    }
};
