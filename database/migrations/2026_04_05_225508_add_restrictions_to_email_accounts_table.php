<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->boolean('can_send')->default(true)->after('status');
            $table->boolean('can_receive')->default(true)->after('can_send');
            $table->unsignedInteger('max_send_per_hour')->default(0)->after('can_receive'); // 0 = unlimited
            $table->unsignedInteger('max_send_per_day')->default(0)->after('max_send_per_hour'); // 0 = unlimited
        });
    }

    public function down(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->dropColumn(['can_send', 'can_receive', 'max_send_per_hour', 'max_send_per_day']);
        });
    }
};
