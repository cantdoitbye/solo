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
        Schema::table('events', function (Blueprint $table) {
           

             $table->string('current_step', 50)->default('basic_info')->after('status');
            $table->json('step_completed_at')->nullable()->after('current_step');
            $table->timestamp('preview_generated_at')->nullable()->after('step_completed_at');
            $table->string('session_id')->nullable()->after('preview_generated_at');
            $table->integer('gender_composition_value')->nullable()->after('gender_composition');
            
            // Add indexes for performance
            $table->index(['host_id', 'current_step']);
            $table->index(['session_id']);
            $table->index(['status', 'current_step']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
             Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['host_id', 'current_step']);
            $table->dropIndex(['session_id']);
            $table->dropIndex(['status', 'current_step']);
            
            $table->dropColumn([
                'current_step',
                'step_completed_at',
                'preview_generated_at',
                'session_id'
            ]);
        });
        });
    }
};
