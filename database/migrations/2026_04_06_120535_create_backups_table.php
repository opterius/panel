<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->string('username');
            $table->string('filename');
            $table->string('type'); // full, files, databases, email
            $table->decimal('size_mb', 10, 2)->default(0);
            $table->string('status'); // completed, failed, restoring
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
