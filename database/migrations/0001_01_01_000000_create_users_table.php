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
        Schema::create('users', function (Blueprint $table) {
           $table->id();
            $table->string('phone_number')->unique();
            $table->string('country_code', 5)->default('+1');
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('otp_code', 6)->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->enum('connection_type', ['social', 'dating', 'both'])->nullable();
            $table->integer('search_radius')->default(25); // in km
            $table->string('referral_code', 10)->unique()->nullable();
            $table->string('used_referral_code', 10)->nullable();
            $table->integer('referral_points')->default(0);
            $table->json('discovery_sources')->nullable(); // how they heard about Solo
            $table->json('interests')->nullable();
            $table->text('bio')->nullable();
            $table->json('introduction_answers')->nullable();
            $table->boolean('onboarding_completed')->default(false);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
