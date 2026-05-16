<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('batch_id')->constrained('notification_batches')->cascadeOnDelete();
            $table->string('recipient_id', 128);
            $table->string('channel', 16);
            $table->string('priority', 16);
            $table->string('message_hash', 64);
            $table->string('status', 16);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('provider_message_id')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('dropped_at')->nullable();
            $table->timestamps();

            $table->index(['recipient_id', 'created_at']);
            $table->index(['status', 'priority']);
            $table->unique(['batch_id', 'recipient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
