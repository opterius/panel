<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Domain extends Model
{
    protected $fillable = [
        'server_id', 'account_id', 'parent_id', 'domain', 'document_root', 'php_version', 'status',
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
}
