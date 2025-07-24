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
        Schema::create('one_on_one_date_bookings', function (Blueprint $table) {
             $table->id();
            $table->foreignId('one_on_one_date_id')->constrained('one_on_one_dates')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('tokens_paid', 8, 2)->default(0);
            $table->enum('status', ['booked', 'cancelled'])->default('booked');
            $table->timestamp('booked_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            
            $table->unique(['one_on_one_date_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('one_on_one_date_bookings');
    }
};
