<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receiver_notification', function (Blueprint $table) {
            $table->id();

            $table->foreignId('receiver_id')
                ->constrained('receiver')
                ->cascadeOnDelete();

            $table->foreignId('notification_id')
                ->constrained('notification')
                ->cascadeOnDelete();

            $table->string('status', 20);
            $table->unsignedSmallInteger('retry_count')->default(0);
            $table->timestampsTz();

            $table->unique(['receiver_id', 'notification_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receiver_notification');
    }
};
