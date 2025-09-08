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
                 $table->enum('status', ['active', 'blocked', 'inactive'])->default('active')->after('default_language');
            $table->timestamp('blocked_at')->nullable()->after('status');
            $table->text('block_reason')->nullable()->after('blocked_at');
       
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
                        $table->dropColumn(['status', 'blocked_at', 'block_reason']);

        });
    }
};
