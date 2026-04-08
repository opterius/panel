<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtectedDirectoryUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'protected_directory_id',
        'username',
        'password_hash',
    ];

    protected $hidden = ['password_hash'];

    public function directory(): BelongsTo
    {
        return $this->belongsTo(ProtectedDirectory::class, 'protected_directory_id');
    }
}
