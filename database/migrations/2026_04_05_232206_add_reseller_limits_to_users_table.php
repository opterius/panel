<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('reseller_max_accounts')->default(0)->after('role');
            $table->unsignedBigInteger('reseller_max_disk')->default(0)->after('reseller_max_accounts');       // MB, 0 = unlimited
            $table->unsignedBigInteger('reseller_max_bandwidth')->default(0)->after('reseller_max_disk');       // MB, 0 = unlimited
            $table->unsignedInteger('reseller_max_domains')->default(0)->after('reseller_max_bandwidth');
            $table->unsignedInteger('reseller_max_databases')->default(0)->after('reseller_max_domains');
            $table->unsignedInteger('reseller_max_email')->default(0)->after('reseller_max_databases');
        });

        // Make packages owner-aware (resellers create their own)
        Schema::table('packages', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            // owner_id NULL = global (admin), owner_id = reseller's packages
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'reseller_max_accounts', 'reseller_max_disk', 'reseller_max_bandwidth',
                'reseller_max_domains', 'reseller_max_databases', 'reseller_max_email',
            ]);
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropColumn('owner_id');
        });
    }
};
