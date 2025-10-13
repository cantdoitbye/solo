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
        Schema::create('webhook_logs', function (Blueprint $table) {
           $table->id();
            $table->string('source')->default('fluidpay');
            $table->text('payload');
            $table->text('response')->nullable();
            $table->integer('status_code')->nullable();
            $table->string('event_type')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
