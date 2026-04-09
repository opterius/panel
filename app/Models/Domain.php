<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Domain extends Model
{
    protected $fillable = [
        'server_id', 'account_id', 'parent_id', 'staging_source_id', 'domain', 'document_root', 'php_version', 'htaccess_enabled', 'status',
    ];

    protected $casts = [
        'htaccess_enabled' => 'boolean',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'parent_id');
    }

    public function subdomains(): HasMany
    {
        return $this->hasMany(Domain::class, 'parent_id');
    }

    public function isSubdomain(): bool
    {
        return $this->parent_id !== null;
    }

    public function sslCertificate(): HasOne
    {
        return $this->hasOne(SslCertificate::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(DomainAlias::class);
    }

    public function redirects(): HasMany
    {
        return $this->hasMany(Redirect::class);
    }

    public function nginxDirective(): HasOne
    {
        return $this->hasOne(NginxDirective::class);
    }

    public function protectedDirectories(): HasMany
    {
        return $this->hasMany(ProtectedDirectory::class);
    }

    public function hotlinkProtection(): HasOne
    {
        return $this->hasOne(HotlinkProtection::class);
    }

    public function cdnZone(): HasOne
    {
        return $this->hasOne(CdnZone::class);
    }

    /**
     * If this domain is a staging clone, returns the source production domain.
     */
    public function stagingSource(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'staging_source_id');
    }

    /**
     * Existing staging clones of this domain (typically 0 or 1).
     */
    public function stagingClones(): HasMany
    {
        return $this->hasMany(Domain::class, 'staging_source_id');
    }

    public function isStaging(): bool
    {
        return $this->staging_source_id !== null;
    }
}
