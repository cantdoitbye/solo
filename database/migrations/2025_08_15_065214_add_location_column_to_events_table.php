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
            // Add reference to SuggestedLocation
            $table->unsignedBigInteger('suggested_location_id')->nullable()->after('session_id');
            
            // Add foreign key constraint
            $table->foreign('suggested_location_id')->references('id')->on('suggested_locations')->onDelete('set null');
            
            // Add index for performance
            $table->index('suggested_location_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('events', function (Blueprint $table) {
            // Drop foreign key and column
            $table->dropForeign(['suggested_location_id']);
            $table->dropIndex(['suggested_location_id']);
            $table->dropColumn('suggested_location_id');
        });
    }
};
