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
        Schema::create('event_menus', function (Blueprint $table) {
              $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_url');
            $table->string('mime_type');
            $table->bigInteger('file_size'); // in bytes
            $table->integer('width')->nullable(); // for images
            $table->integer('height')->nullable(); // for images
            $table->string('upload_session_id')->nullable(); // To group uploads before event creation
            $table->boolean('is_attached_to_event')->default(false);
            $table->integer('sort_order')->default(0); // For ordering multiple menu images
            $table->timestamps();
            
            // Indexes
            $table->index(['event_id', 'sort_order']);
            $table->index(['is_attached_to_event', 'upload_session_id']);
       
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_menus');
    }
};
