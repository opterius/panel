<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ssl_certificates', function (Blueprint $table) {
            $table->string('type', 20)->default('letsencrypt')->change();
            $table->string('progress_step', 30)->nullable()->after('auto_renew');
            $table->string('progress_message', 255)->nullable()->after('progress_step');
        });
    }

    public function down(): void
    {
        Schema::table('ssl_certificates', function (Blueprint $table) {
            $table->dropColumn(['progress_step', 'progress_message']);
        });
    }
};
