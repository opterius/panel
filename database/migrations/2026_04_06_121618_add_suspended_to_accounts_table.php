<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('suspended')->default(false)->after('ssh_enabled');
            $table->timestamp('suspended_at')->nullable()->after('suspended');
            $table->string('suspend_reason')->nullable()->after('suspended_at');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn(['suspended', 'suspended_at', 'suspend_reason']);
        });
    }
};
