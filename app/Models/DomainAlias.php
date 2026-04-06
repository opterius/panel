<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainAlias extends Model
{
    protected $fillable = ['domain_id', 'alias_domain', 'status'];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
