<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProtectedDirectory extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_id',
        'path',
        'label',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(ProtectedDirectoryUser::class);
    }
}
