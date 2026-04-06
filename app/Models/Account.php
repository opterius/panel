<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'server_id', 'user_id', 'package_id', 'username', 'home_directory', 'disk_quota', 'php_version', 'ssh_enabled',
        'suspended', 'suspended_at', 'suspend_reason',
        'whmcs_service_id', 'whmcs_client_id', 'created_via',
    ];

    protected function casts(): array
    {
        return [
            'ssh_enabled' => 'boolean',
            'suspended' => 'boolean',
            'suspended_at' => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function databases(): HasMany
    {
        return $this->hasMany(Database::class);
    }

    public function cronJobs(): HasMany
    {
        return $this->hasMany(CronJob::class);
    }

    /**
     * Users who have access to this account (collaborators).
     */
    public function collaborators()
    {
        return $this->belongsToMany(User::class, 'account_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Check if a user has a specific permission on this account.
     */
    public function userCan(User $user, string $permission): bool
    {
        // Account owner (user_id) always has full access
        if ($this->user_id === $user->id) return true;

        // Admin role always has full access
        if ($user->isAdmin()) return true;

        $pivot = $this->collaborators()->where('user_id', $user->id)->first();
        if (!$pivot) return false;

        $role = $pivot->pivot->role;

        $permissions = [
            'owner'         => ['files', 'databases', 'email', 'ssh', 'cron', 'ssl', 'dns', 'settings'],
            'admin'         => ['files', 'databases', 'email', 'ssh', 'cron', 'ssl', 'dns'],
            'developer'     => ['files', 'databases', 'ssh', 'cron'],
            'designer'      => ['files'],
            'email_manager' => ['email'],
            'viewer'        => [],
        ];

        return in_array($permission, $permissions[$role] ?? []);
    }
}
