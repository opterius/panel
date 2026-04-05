<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => 'Gabriel Chimilevschi',
            'email' => 'gabriel1978@gmail.com',
            'password' => bcrypt('admin'),
            'role' => 'admin',
        ]);

        Package::create([
            'user_id' => $admin->id,
            'name' => 'Default',
            'description' => 'Default hosting package',
            'php_versions' => config('opterius.php_versions'),
            'default_php_version' => config('opterius.default_php_version'),
            'disk_quota' => 0,
            'bandwidth' => 0,
            'max_subdomains' => 0,
            'max_databases' => 0,
            'max_email_accounts' => 0,
            'ssl_enabled' => true,
            'cron_jobs_enabled' => true,
            'is_default' => true,
        ]);
    }
}
