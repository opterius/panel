<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedInteger('max_domains')->default(0)->after('max_subdomains');       // 0 = unlimited
            $table->unsignedInteger('max_php_workers')->default(5)->after('max_email_accounts'); // pm.max_children per domain
            $table->unsignedInteger('memory_per_process')->default(256)->after('max_php_workers'); // MB, php memory_limit
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['max_domains', 'max_php_workers', 'memory_per_process']);
        });
    }
};
