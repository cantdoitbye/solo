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
        Schema::create('events', function (Blueprint $table) {
         $table->id();
            $table->foreignId('host_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description');
            $table->json('tags')->nullable();
            
            // Date & Time
            $table->date('event_date');
            $table->time('event_time');
            $table->string('timezone')->default('UTC');
            
            // Venue & Location (Google Maps Integration)
            $table->foreignId('venue_type_id')->constrained('venue_types');
            $table->foreignId('venue_category_id')->constrained('venue_categories');
            $table->string('venue_name')->nullable();
            $table->text('venue_address')->nullable();
            $table->string('google_place_id')->nullable(); // Google Places API ID
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->json('google_place_details')->nullable(); // Store full Google place details
            
            // Attendees Setup
            $table->integer('min_group_size')->default(2);
            $table->integer('max_group_size')->nullable(); // null = infinity
            $table->boolean('gender_rule_enabled')->default(false);
            $table->string('gender_composition')->nullable(); // specific ratios or rules
            $table->integer('min_age')->default(18);
            $table->integer('max_age')->default(100);
            $table->json('allowed_genders')->nullable(); // ['male', 'female', 'gay', 'lesbian', 'trans', 'bisexual']
            
            // Token & Payment
            $table->decimal('token_cost_per_attendee', 8, 2)->default(0);
            $table->decimal('total_tokens_display', 8, 2)->nullable(); // calculated field
            
            // Event History & Media
            $table->json('media_urls')->nullable(); // array of uploaded media
            $table->text('past_event_description')->nullable();
            
            // Host Responsibilities
            $table->string('cancellation_policy')->default('no_refund'); // no_refund, partial_refund, full_refund
            $table->json('itinerary_url')->nullable(); // uploaded itinerary file
            $table->boolean('host_responsibilities_accepted')->default(false);
            
            // Event Status & Approval
            $table->enum('status', ['draft', 'published', 'cancelled', 'completed'])->default('draft');
            $table->boolean('is_approved')->default(true); // Admin approval (default true for now)
            $table->foreignId('approved_by')->nullable()->constrained('users'); // Admin who approved
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable(); // If rejected by admin
            $table->timestamp('published_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['event_date', 'event_time']);
            $table->index(['city', 'state', 'country']);
            $table->index(['status', 'published_at']);
            $table->index(['host_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
