<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cron_run_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cron_job_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->integer('exit_code')->nullable();
            $table->longText('stdout')->nullable();
            $table->longText('stderr')->nullable();
            $table->timestamps();

            $table->index(['cron_job_id', 'started_at']);
        });

        // Friendly description column for the visual builder.
        Schema::table('cron_jobs', function (Blueprint $table) {
            $table->string('description')->nullable()->after('command');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_run_history');
        Schema::table('cron_jobs', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
