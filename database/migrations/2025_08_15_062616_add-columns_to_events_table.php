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
         Schema::table('events', function (Blueprint $table) {
            // Remove complex fields that are no longer needed
            $table->dropColumn([
                'tags',                     // No tags selection
                'media_urls',               // No media upload
                'itinerary_url',            // No itinerary upload  
                'past_event_description',   // No event history step
            ]);
            
            // Add new simplified fields
            $table->boolean('age_restriction_disabled')->default(false)->after('max_age');
            
            // Set defaults for required fields
            $table->decimal('token_cost_per_attendee', 8, 2)->default(5.00)->change();
            $table->boolean('host_responsibilities_accepted')->default(true)->change();
            
            // Make venue fields optional (since auto-assigned)
            $table->unsignedBigInteger('venue_type_id')->nullable()->change();
            $table->unsignedBigInteger('venue_category_id')->nullable()->change();
            
            // Indexes for performance
            // $table->index(['status', 'published_at']);
            $table->index(['city', 'state', 'event_date']); // For location-based queries
            $table->index(['min_age', 'max_age', 'age_restriction_disabled']); // For age-based visibility
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('events', function (Blueprint $table) {
            // Restore removed fields
            $table->json('tags')->nullable();
            $table->json('media_urls')->nullable();
            $table->json('itinerary_url')->nullable();
            $table->text('past_event_description')->nullable();
            
            // Remove new fields
            $table->dropColumn('age_restriction_disabled');
            
            // Revert defaults
            $table->decimal('token_cost_per_attendee', 8, 2)->nullable()->change();
            $table->boolean('host_responsibilities_accepted')->default(false)->change();
            
            // Revert venue fields to required
            $table->unsignedBigInteger('venue_type_id')->nullable(false)->change();
            $table->unsignedBigInteger('venue_category_id')->nullable(false)->change();
            
            // Drop added indexes
            $table->dropIndex(['status', 'published_at']);
            $table->dropIndex(['city', 'state', 'event_date']);
            $table->dropIndex(['min_age', 'max_age', 'age_restriction_disabled']);
        });
    }
};
