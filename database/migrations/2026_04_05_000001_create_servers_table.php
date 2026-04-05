<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('ip_address');
            $table->string('hostname')->nullable();
            $table->string('os')->nullable();
            $table->string('os_version')->nullable();
            $table->string('agent_url')->nullable();
            $table->text('agent_token')->nullable();
            $table->enum('status', ['pending', 'online', 'offline', 'error'])->default('pending');
            $table->timestamp('last_ping_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
