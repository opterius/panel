<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            // When this domain is a staging copy of another domain, points to the source.
            $table->foreignId('staging_source_id')->nullable()->after('parent_id')
                ->constrained('domains')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropConstrainedForeignId('staging_source_id');
        });
    }
};
