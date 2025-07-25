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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_room_id')->constrained('chat_rooms')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->enum('message_type', ['text', 'image', 'file', 'voice', 'system'])->default('text');
            $table->text('content');
            $table->string('file_url', 500)->nullable();
            $table->string('file_name')->nullable();
            $table->bigInteger('file_size')->nullable(); // File size in bytes
            $table->integer('duration')->nullable(); // For voice messages (seconds)
            $table->text('transcription')->nullable(); // Auto-generated transcription for voice
            $table->foreignId('reply_to_message_id')->nullable()->constrained('messages')->onDelete('set null');
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->json('reactions')->nullable(); // Store emoji reactions
            $table->json('metadata')->nullable(); // Additional message metadata
            $table->timestamps();

            // Indexes
            $table->index(['chat_room_id']);
            $table->index(['sender_id']);
            $table->index(['created_at']);
            $table->index(['reply_to_message_id']);
            $table->index(['message_type']);
            $table->index(['chat_room_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
