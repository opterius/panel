<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address');
            $table->enum('type', ['shared', 'dedicated'])->default('shared');
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete(); // null = available
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['server_id', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_addresses');
    }
};
