<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 20);
            $table->string('type', 20);
            $table->text('text');
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification');
    }
};
