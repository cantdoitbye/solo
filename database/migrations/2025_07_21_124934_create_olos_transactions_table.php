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
        Schema::create('olos_transactions', function (Blueprint $table) {
             $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['credit', 'debit']); // credit = earned, debit = spent
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_before', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->string('transaction_type'); // 'registration_bonus', 'event_join', 'event_refund', 'purchase', 'referral_bonus'
            $table->string('description');
            $table->json('metadata')->nullable(); // Store additional info like event_id, payment_id, etc.
            $table->string('reference_id')->nullable(); // For linking to events, payments, etc.
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'transaction_type']);
            $table->index(['reference_id', 'transaction_type']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olos_transactions');
    }
};
