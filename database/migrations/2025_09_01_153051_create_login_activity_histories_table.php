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
        Schema::create('login_activity_histories', function (Blueprint $table) {
           $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_name')->nullable(); // iPhone 16 Pro Max, iPad Pro, etc.
            $table->string('device_type')->nullable(); // mobile, tablet, desktop
            $table->string('os_name')->nullable(); // iOS, Android
            $table->string('os_version')->nullable(); // 17.1
            $table->string('app_version')->nullable(); // 1.0.0
            $table->string('ip_address')->nullable();
            $table->string('location')->nullable(); // California, USA
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('login_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_activity_histories');
    }
};
