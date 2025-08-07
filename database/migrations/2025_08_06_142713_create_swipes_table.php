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
        Schema::create('swipes', function (Blueprint $table) {
              $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('target_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('action', ['like', 'pass', 'super_like'])->index();
            $table->boolean('is_match')->default(false);
            $table->timestamp('matched_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Prevent duplicate swipes
            $table->unique(['user_id', 'target_user_id']);
            
            // Indexes for performance
            $table->index(['user_id', 'action']);
            $table->index(['target_user_id', 'action']);
            $table->index('is_match');
            $table->index('matched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('swipes');
    }
};
