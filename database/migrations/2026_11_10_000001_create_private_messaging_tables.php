<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_conversations', function (Blueprint $table) {
            $table->id();
            $table->char('pair_key', 64)->unique();
            $table->string('subject', 150)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('last_message_at')->index();
            $table->timestamps();
        });

        Schema::create('message_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('message_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('joined_at');
            $table->timestamps();
            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'conversation_id']);
        });

        Schema::create('school_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('message_conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->timestamp('delivered_at');
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_messages');
        Schema::dropIfExists('message_participants');
        Schema::dropIfExists('message_conversations');
    }
};
