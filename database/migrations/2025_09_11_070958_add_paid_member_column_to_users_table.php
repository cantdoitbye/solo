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
        Schema::table('users', function (Blueprint $table) {
                 $table->boolean('is_paid_member')->default(false)->after('referral_points');
            $table->string('plan_type')->nullable()->after('is_paid_member'); // 'basic', 'premium', etc.
            $table->timestamp('paid_member_since')->nullable()->after('plan_type');
            
       
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
              $table->dropColumn([
                'is_paid_member',
                'plan_type', 
                'paid_member_since',
                'is_referred_friend',
                'has_used_free_event',
                'free_event_used_at'
            ]);
        });
    }
};
