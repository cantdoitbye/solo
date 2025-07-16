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
            // Drop the old unique constraint on phone_number only
            $table->dropUnique(['phone_number']);
            
            // Add new unique constraint on phone_number + country_code combination
            $table->unique(['phone_number', 'country_code'], 'users_phone_country_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('users', function (Blueprint $table) {
            // Drop the combined unique constraint
            $table->dropUnique('users_phone_country_unique');
            
            // Restore the old unique constraint on phone_number only
            $table->unique('phone_number');
        });
    }
};
