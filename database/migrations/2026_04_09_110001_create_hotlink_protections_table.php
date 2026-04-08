<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotlink_protections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete()->unique();
            $table->boolean('enabled')->default(false);
            $table->json('allowed_domains')->nullable();    // array of hostnames whitelisted
            $table->json('allowed_extensions')->nullable(); // array of file extensions to protect
            $table->boolean('allow_direct')->default(true); // allow direct browser hits (no referer)
            $table->string('redirect_url')->nullable();     // optional redirect for blocked requests
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotlink_protections');
    }
};
