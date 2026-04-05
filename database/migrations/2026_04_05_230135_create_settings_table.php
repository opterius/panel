<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->index(); // email, server, panel
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Default email settings
        $defaults = [
            ['group' => 'email', 'key' => 'email_default_quota', 'value' => '500'],
            ['group' => 'email', 'key' => 'email_max_send_per_hour', 'value' => '100'],
            ['group' => 'email', 'key' => 'email_max_send_per_day', 'value' => '500'],
            ['group' => 'email', 'key' => 'email_sending_enabled', 'value' => '1'],
            ['group' => 'email', 'key' => 'email_receiving_enabled', 'value' => '1'],
            ['group' => 'email', 'key' => 'email_max_attachment_mb', 'value' => '25'],
            ['group' => 'email', 'key' => 'email_max_accounts_per_domain', 'value' => '0'],
        ];

        foreach ($defaults as $setting) {
            $setting['created_at'] = now();
            $setting['updated_at'] = now();
            DB::table('settings')->insert($setting);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
