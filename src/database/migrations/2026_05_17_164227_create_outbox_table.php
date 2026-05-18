<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 100);
            $table->jsonb('payload');
            $table->string('priority', 20);
            $table->timestampTz('send_after')->useCurrent();
            $table->boolean('is_sent')->default(false);
            $table->timestampsTz();
        });

        DB::statement('
            CREATE INDEX idx_outbox_ready_to_send
            ON outbox (
                send_after,
                priority,
                created_at
            )
            WHERE is_sent = false
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox');
    }
};
