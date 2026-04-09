<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores the database user's plaintext password, encrypted at rest with
     * Laravel's app key. Required so the user can:
     *
     *  - Look up their DB password after a cPanel import (it would otherwise
     *    be a randomly generated value the user has no way to discover)
     *  - Use the planned phpMyAdmin Single Sign-On feature without us having
     *    to do a fragile reset-on-click dance for every login
     *
     * Existing rows get NULL — those passwords were lost at creation time
     * and the user must reset them manually if they want to use the feature.
     */
    public function up(): void
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->text('encrypted_password')->nullable()->after('db_username');
        });
    }

    public function down(): void
    {
        Schema::table('databases', function (Blueprint $table) {
            $table->dropColumn('encrypted_password');
        });
    }
};
