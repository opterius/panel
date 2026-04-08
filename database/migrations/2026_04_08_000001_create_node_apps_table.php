<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('node_apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('domain_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');                      // short app name (alphanumeric, used as PM2 name suffix)
            $table->string('startup_command');           // e.g. "node server.js" or "npm start"
            $table->string('working_dir');               // absolute path on server
            $table->unsignedSmallInteger('port');        // port the app listens on
            $table->enum('status', ['running', 'stopped', 'error'])->default('stopped');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('node_apps');
    }
};
