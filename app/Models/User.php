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
        'reseller_max_accounts',
        'reseller_max_disk',
        'reseller_max_bandwidth',
        'reseller_max_domains',
        'reseller_max_databases',
        'reseller_max_email',
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
        return Account::where('user_id', $this->id)
            ->orWhereHas('collaborators', fn ($q) => $q->where('users.id', $this->id));
    }

    /**
     * Scope: accessible account IDs for use in whereIn queries.
     */
    public function accessibleAccountIds(): array
    {
        return $this->accessibleAccounts()->pluck('id')->toArray();
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
