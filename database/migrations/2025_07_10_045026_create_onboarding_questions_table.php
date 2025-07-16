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
        Schema::create('onboarding_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question_key')->unique(); // e.g., 'what_i_care_about'
            $table->string('question_text');
            $table->string('placeholder_text')->nullable();
            $table->enum('input_type', ['text', 'textarea', 'select', 'multiselect'])->default('text');
            $table->integer('max_length')->default(200);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_questions');
    }
};
