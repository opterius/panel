<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cpanel_migrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('initiated_by');
            $table->string('source_type')->default('cpanel_backup'); // cpanel_backup, url
            $table->string('source_path');
            $table->string('original_username')->nullable();
            $table->string('target_username')->nullable();
            $table->string('main_domain')->nullable();
            $table->json('manifest')->nullable();
            $table->json('options')->nullable();
            $table->json('result')->nullable();
            $table->string('status')->default('pending'); // pending, parsing, previewing, running, completed, partial, failed
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('current_step')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cpanel_migrations');
    }
};
