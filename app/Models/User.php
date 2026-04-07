<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'locale',
        'reseller_max_accounts',
        'reseller_max_disk',
        'reseller_max_bandwidth',
        'reseller_max_domains',
        'reseller_max_databases',
        'reseller_max_email',
        'reseller_acl',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'reseller_acl' => 'array',
        ];
    }

    /**
     * All available reseller ACL permissions grouped by category.
     */
    public static function resellerAclDefinitions(): array
    {
        return [
            'Account Management' => [
                'account.create'    => 'Create accounts',
                'account.suspend'   => 'Suspend / unsuspend accounts',
                'account.terminate' => 'Terminate (delete) accounts',
                'account.edit'      => 'Edit account details',
                'account.password'  => 'Change account passwords',
                'account.upgrade'   => 'Change account packages',
            ],
            'Domain & DNS' => [
                'domain.subdomains' => 'Manage subdomains',
                'domain.aliases'    => 'Manage domain aliases',
                'domain.redirects'  => 'Manage URL redirects',
                'dns.manage'        => 'Manage DNS zones and records',
                'ssl.manage'        => 'Manage SSL certificates',
            ],
            'Email' => [
                'email.accounts'      => 'Create / delete email accounts',
                'email.forwarders'    => 'Manage email forwarders',
                'email.autoresponder' => 'Manage autoresponders',
            ],
            'Files & Databases' => [
                'files.filemanager' => 'Access file manager',
                'files.ftp'        => 'Manage FTP accounts',
                'files.ssh'        => 'Manage SSH access',
                'db.manage'        => 'Create / delete databases',
            ],
            'Software' => [
                'software.wordpress' => 'Install WordPress',
                'software.laravel'   => 'Install Laravel',
            ],
            'Server Features' => [
                'cron.manage'      => 'Manage cron jobs',
                'php.switch'       => 'Change PHP version',
                'backup.manage'    => 'Create / restore backups',
                'migration.import' => 'Import cPanel backups',
            ],
            'Administration' => [
                'packages.manage'    => 'Create / edit packages',
                'packages.assign'    => 'Assign packages to accounts',
                'activity.view'      => 'View activity log',
                'api_keys.manage'    => 'Manage API keys',
                'collaborators.manage' => 'Manage team access / collaborators',
            ],
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isReseller(): bool
    {
        return $this->role === 'reseller';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Get all accounts this user can access (owned + collaborator).
     */
    public function accessibleAccounts()
    {
        return Account::where(function ($query) {
            $query->where('user_id', $this->id)
                ->orWhereHas('collaborators', fn ($q) => $q->where('users.id', $this->id));
        });
    }

    /**
     * Scope: accessible account IDs for use in whereIn queries.
     */
    public function accessibleAccountIds(): array
    {
        return $this->accessibleAccounts()->pluck('id')->toArray();
    }

    /**
     * Get the currently selected account from session, or default to first accessible.
     * Used in Hosting Mode where the user is "inside" one specific account at a time (cPanel-style).
     */
    public function currentAccount(): ?Account
    {
        $selectedId = session('current_account_id');
        $accounts = $this->accessibleAccounts()->with('server', 'package')->get();

        if ($selectedId) {
            $account = $accounts->firstWhere('id', $selectedId);
            if ($account) return $account;
        }

        return $accounts->first();
    }

    /**
     * Returns the current account ID wrapped in an array.
     * Drop-in replacement for accessibleAccountIds() in Hosting Mode controllers
     * where each session should be scoped to ONE active account (cPanel-style).
     */
    public function currentAccountIds(): array
    {
        $current = $this->currentAccount();
        return $current ? [$current->id] : [];
    }

    /**
     * Query builder scoped to the user's currently selected account only.
     * Drop-in replacement for accessibleAccounts() in Hosting Mode controllers.
     */
    public function scopedToCurrent()
    {
        $ids = $this->currentAccountIds();
        return Account::whereIn('id', $ids ?: [0]);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class, 'owner_id');
    }

    /**
     * Get the reseller's current usage against their limits.
     */
    public function resellerUsage(): array
    {
        $accounts = $this->accounts()->count();
        $domains = Domain::whereHas('account', fn ($q) => $q->where('user_id', $this->id))->count();
        $databases = Database::whereHas('account', fn ($q) => $q->where('user_id', $this->id))->count();
        $emails = EmailAccount::whereHas('domain.account', fn ($q) => $q->where('user_id', $this->id))->count();

        return [
            'accounts'  => ['used' => $accounts, 'limit' => $this->reseller_max_accounts],
            'domains'   => ['used' => $domains, 'limit' => $this->reseller_max_domains],
            'databases' => ['used' => $databases, 'limit' => $this->reseller_max_databases],
            'email'     => ['used' => $emails, 'limit' => $this->reseller_max_email],
        ];
    }

    /**
     * Check if reseller has a specific ACL permission.
     * Admins always have all permissions. Non-resellers always return false.
     */
    public function resellerCan(string $permission): bool
    {
        if ($this->isAdmin()) return true;
        if (!$this->isReseller()) return false;

        $acl = $this->reseller_acl ?? [];

        // If ACL is empty (not configured yet), allow all (backwards compatibility)
        if (empty($acl)) return true;

        return in_array($permission, $acl);
    }

    /**
     * Check if reseller can create more of a resource.
     */
    public function resellerCanCreate(string $resource): bool
    {
        $usage = $this->resellerUsage();
        if (!isset($usage[$resource])) return true;
        if ($usage[$resource]['limit'] === 0) return true; // 0 = unlimited
        return $usage[$resource]['used'] < $usage[$resource]['limit'];
    }
}
