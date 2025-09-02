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
        if (!Schema::hasColumn('users', 'two_factor_enabled')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('two_factor_enabled')->default(false)->after('fcm_token_updated_at');
            });
        }

        if (!Schema::hasColumn('users', 'push_notifications_enabled')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('push_notifications_enabled')->default(true)->after('two_factor_enabled');
            });
        }

        if (!Schema::hasColumn('users', 'sound_alerts_enabled')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('sound_alerts_enabled')->default(true)->after('push_notifications_enabled');
            });
        }

        if (!Schema::hasColumn('users', 'selected_theme')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('selected_theme')->default('system_default')->after('sound_alerts_enabled');
            });
        }

        if (!Schema::hasColumn('users', 'default_language')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('default_language')->default('english_us')->after('selected_theme');
            });
        }

        if (!Schema::hasColumn('users', 'account_settings_updated_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('account_settings_updated_at')->nullable()->after('default_language');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
