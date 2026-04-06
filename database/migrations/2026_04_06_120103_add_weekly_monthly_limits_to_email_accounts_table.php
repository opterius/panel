<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->unsignedInteger('max_send_per_week')->default(0)->after('max_send_per_day');
            $table->unsignedInteger('max_send_per_month')->default(0)->after('max_send_per_week');
        });
    }

    public function down(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->dropColumn(['max_send_per_week', 'max_send_per_month']);
        });
    }
};
