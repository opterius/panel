<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->boolean('cdn_enabled')->default(false)->after('php_switch_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('cdn_enabled');
        });
    }
};
