<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->timestamp('recorded_at')->index();

            // Core metrics — kept lightweight so storage is cheap.
            $table->decimal('cpu_percent', 5, 2)->default(0);
            $table->decimal('mem_percent', 5, 2)->default(0);
            $table->unsignedBigInteger('mem_used_mb')->default(0);
            $table->unsignedBigInteger('mem_total_mb')->default(0);
            $table->decimal('disk_percent', 5, 2)->default(0);
            $table->decimal('load_avg_1', 6, 2)->default(0);
            $table->decimal('load_avg_5', 6, 2)->default(0);
            $table->decimal('load_avg_15', 6, 2)->default(0);
            $table->unsignedBigInteger('network_in_kb')->default(0);
            $table->unsignedBigInteger('network_out_kb')->default(0);

            // Composite index for time-range queries per server.
            $table->index(['server_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_metrics');
    }
};
