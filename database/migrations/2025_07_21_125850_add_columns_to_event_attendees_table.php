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
        Schema::table('event_attendees', function (Blueprint $table) {
              $table->integer('total_members')->default(1)->after('tokens_paid');
            $table->decimal('cost_per_member', 8, 2)->default(0)->after('total_members');
            $table->decimal('total_cost', 8, 2)->default(0)->after('cost_per_member');
            $table->json('members_data')->nullable()->after('total_cost'); // Store member details as JSON
            
            // Add indexes for performance
            $table->index(['user_id', 'joined_at']);
            $table->index(['event_id', 'total_members']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_attendees', function (Blueprint $table) {
             $table->dropIndex(['user_id', 'joined_at']);
            $table->dropIndex(['event_id', 'total_members']);
            $table->dropColumn(['total_members', 'cost_per_member', 'total_cost']);
        });
    }
};
