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
             $table->boolean('two_factor_enabled')->default(false)->after('fcm_token_updated_at');
            $table->boolean('push_notifications_enabled')->default(true)->after('two_factor_enabled');
            $table->boolean('sound_alerts_enabled')->default(true)->after('push_notifications_enabled');
            $table->string('selected_theme')->nullable()->after('sound_alerts_enabled'); // system_default, light, dark
            $table->string('default_language')->nullable()->after('selected_theme'); // english_us, spanish, french, etc.
            $table->timestamp('account_settings_updated_at')->nullable()->after('default_language');
       
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
              $table->dropColumn([
                'two_factor_enabled',
                'push_notifications_enabled', 
                'sound_alerts_enabled',
                'selected_theme',
                'default_language',
                'account_settings_updated_at'
            ]);
        });
    }
};
