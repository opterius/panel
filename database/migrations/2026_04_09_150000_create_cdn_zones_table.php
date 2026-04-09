<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cdn_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('provider', 32)->default('bunnycdn');
            $table->boolean('enabled')->default(false);

            // BunnyCDN-specific identifiers
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->string('zone_name', 100)->nullable();
            $table->string('cdn_hostname')->nullable();   // e.g. opterius-example-com.b-cdn.net

            // Asset paths the agent should rewrite via Nginx sub_filter
            $table->json('rewrite_paths')->nullable();    // ["/wp-content/", "/wp-includes/"]

            // Diagnostics
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cdn_zones');
    }
};
