<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protected_directories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();
            $table->string('path'); // relative to document root, e.g. "admin" or "wp-admin"
            $table->string('label')->nullable(); // realm name shown in browser prompt
            $table->timestamps();

            $table->unique(['domain_id', 'path']);
        });

        Schema::create('protected_directory_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('protected_directory_id')->constrained()->cascadeOnDelete();
            $table->string('username', 64);
            $table->string('password_hash'); // bcrypt/apr1 hash stored in htpasswd
            $table->timestamps();

            $table->unique(['protected_directory_id', 'username']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protected_directory_users');
        Schema::dropIfExists('protected_directories');
    }
};
