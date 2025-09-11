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
        Schema::create('user_referrals', function (Blueprint $table) {
             $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade'); // The user who referred
            $table->foreignId('referred_id')->constrained('users')->onDelete('cascade'); // The user who was referred
            $table->string('referral_code_used'); // The actual referral code that was used
            $table->boolean('referrer_was_paid')->default(false); // Was referrer paid at time of referral
            $table->boolean('is_eligible_for_free_event')->default(false); // Can referred user get free event
            $table->boolean('has_used_free_event')->default(false); // Has referred user used free event
            $table->timestamp('free_event_used_at')->nullable(); // When free event was used
            $table->foreignId('free_event_id')->nullable()->constrained('events')->onDelete('set null'); // Which event was free
            $table->integer('referrer_bonus_points')->default(0); // Points given to referrer
            $table->integer('referred_bonus_points')->default(0); // Points given to referred user
            $table->timestamp('referred_at')->nullable(); // When the referral happened
            $table->timestamps();
            
            // Indexes
            $table->index(['referrer_id', 'referred_at']);
            $table->index(['referred_id']);
            $table->index(['referral_code_used']);
            $table->unique(['referrer_id', 'referred_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_referrals');
    }
};
