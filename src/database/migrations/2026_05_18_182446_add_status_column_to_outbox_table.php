<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outbox', function (Blueprint $table) {
            $table->dropIndex('idx_outbox_ready_to_send');
            $table->dropColumn('is_sent');
            $table->string('status', 20)->default('pending');
        });

        DB::statement("
            CREATE INDEX idx_outbox_ready_to_send
            ON outbox (
                send_after,
                priority,
                created_at
            )
            WHERE status = 'pending'
        ");
    }

    public function down(): void
    {
        Schema::table('outbox', function (Blueprint $table) {
            $table->dropIndex('idx_outbox_ready_to_send');
            $table->dropColumn('status');

            $table->boolean('is_sent')->default(false);
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
};
