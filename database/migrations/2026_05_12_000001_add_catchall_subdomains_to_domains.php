<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->boolean('catchall_subdomains')->default(false)->after('status');
            // Optional custom document root for catch-all. NULL = inherit from
            // main domain (use the same public_html). Set to an absolute path
            // when the SaaS app lives elsewhere than the marketing site.
            $table->string('catchall_document_root', 512)->nullable()->after('catchall_subdomains');
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn(['catchall_subdomains', 'catchall_document_root']);
        });
    }
};
