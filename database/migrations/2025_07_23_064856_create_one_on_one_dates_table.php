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
        Schema::create('one_on_one_dates', function (Blueprint $table) {
          $table->id();
            $table->foreignId('host_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description');
            
            // Date & Time
            $table->date('event_date');
            $table->time('event_time');
            $table->string('timezone')->default('UTC');
            
            // Location
  $table->string('venue_name')->nullable();
            $table->text('venue_address')->nullable();
            $table->string('google_place_id')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->json('google_place_details')->nullable();
                   
            // Olos/Token Cost
            $table->decimal('token_cost', 8, 2)->default(0);
            
            // Media session for multiple images (optional now)
            $table->string('media_session_id')->nullable();
            
            // Request approval status
            $table->boolean('request_approval')->default(false);
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved');
            
            // Status
            $table->enum('status', ['draft', 'published', 'cancelled', 'completed'])->default('published');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['host_id', 'status']);
            $table->index(['event_date', 'event_time']);
            $table->index(['approval_status', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('one_on_one_dates');
    }
};
