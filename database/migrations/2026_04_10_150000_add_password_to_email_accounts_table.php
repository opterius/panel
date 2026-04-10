<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stores the email account's plaintext password (encrypted at rest with
     * Laravel's app key) so the user can look it up later without a reset.
     *
     * `password_preserved` is true when the cPanel import re-used the
     * original password hash from the backup's shadow file — in that case
     * we don't know the plaintext and `encrypted_password` is NULL, but
     * the user's existing credentials still work.
     */
    public function up(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->text('encrypted_password')->nullable()->after('email');
            $table->boolean('password_preserved')->default(false)->after('encrypted_password');
        });
    }

    public function down(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->dropColumn(['encrypted_password', 'password_preserved']);
        });
    }
};
