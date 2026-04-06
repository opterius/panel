<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dns_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->json('records'); // Array of {name, type, content, ttl, priority}
            $table->timestamps();
        });

        // Link templates to packages
        Schema::table('packages', function (Blueprint $table) {
            $table->foreignId('dns_template_id')->nullable()->after('is_default')
                ->constrained('dns_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropForeign(['dns_template_id']);
            $table->dropColumn('dns_template_id');
        });
        Schema::dropIfExists('dns_templates');
    }
};
