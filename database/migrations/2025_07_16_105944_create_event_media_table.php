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
        Schema::create('event_media', function (Blueprint $table) {
             $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Who uploaded
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('file_path');
            $table->string('file_url');
            $table->enum('media_type', ['image', 'video']);
            $table->string('mime_type');
            $table->bigInteger('file_size'); // in bytes
            $table->integer('width')->nullable(); // for images/videos
            $table->integer('height')->nullable(); // for images/videos
            $table->integer('duration')->nullable(); // for videos (in seconds)
            $table->string('upload_session_id')->nullable(); // To group uploads before event creation
            $table->foreignId('event_id')->nullable()->constrained('events')->onDelete('cascade');
            $table->boolean('is_attached_to_event')->default(false);
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'upload_session_id']);
            $table->index(['event_id', 'media_type']);
            $table->index(['is_attached_to_event', 'upload_session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_media');
    }
};
