<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->json('php_versions')->nullable();
            $table->string('default_php_version')->default('8.3');
            $table->unsignedBigInteger('disk_quota')->default(0); // MB, 0 = unlimited
            $table->unsignedBigInteger('bandwidth')->default(0); // MB/month, 0 = unlimited
            $table->unsignedInteger('max_subdomains')->default(0); // 0 = unlimited
            $table->unsignedInteger('max_databases')->default(0); // 0 = unlimited
            $table->unsignedInteger('max_email_accounts')->default(0); // 0 = unlimited
            $table->boolean('ssl_enabled')->default(true);
            $table->boolean('cron_jobs_enabled')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('package_id')->nullable()->after('server_id')->constrained()->nullOnDelete();
            $table->string('php_version')->default('8.3')->after('home_directory');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn(['package_id', 'php_version']);
        });

        Schema::dropIfExists('packages');
    }
};
