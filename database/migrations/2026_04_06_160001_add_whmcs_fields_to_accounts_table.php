<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('whmcs_service_id')->nullable()->after('suspended_at');
            $table->unsignedBigInteger('whmcs_client_id')->nullable()->after('whmcs_service_id');
            $table->string('created_via', 20)->default('panel')->after('whmcs_client_id');

            $table->index('whmcs_service_id');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropIndex(['whmcs_service_id']);
            $table->dropColumn(['whmcs_service_id', 'whmcs_client_id', 'created_via']);
        });
    }
};
