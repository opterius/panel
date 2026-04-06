<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('metric'); // cpu, memory, disk, load
            $table->string('operator'); // >, <
            $table->decimal('threshold', 8, 2); // e.g. 90 for 90%
            $table->unsignedInteger('duration_minutes')->default(5); // must exceed for X minutes
            $table->string('channel'); // email, telegram, slack, discord
            $table->text('channel_config')->nullable(); // JSON: email address, webhook URL, etc.
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
