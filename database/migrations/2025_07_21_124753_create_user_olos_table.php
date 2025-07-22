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
        Schema::create('user_olos', function (Blueprint $table) {
           $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('balance', 10, 2)->default(0);
            $table->decimal('total_earned', 10, 2)->default(0); 
            $table->decimal('total_spent', 10, 2)->default(0); 
            $table->timestamps();
            
            $table->unique('user_id');
            $table->index('balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_olos');
    }
};
