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
        Schema::create('location_images', function (Blueprint $table) {
              $table->id();
            $table->unsignedBigInteger('suggested_location_id');
            $table->string('image_path');
            $table->string('image_url');
            $table->string('original_filename')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('suggested_location_id')->references('id')->on('suggested_locations')->onDelete('cascade');
            
            // Indexes
            $table->index(['suggested_location_id', 'is_primary']);
            $table->index(['suggested_location_id', 'sort_order']);
       
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_images');
    }
};
