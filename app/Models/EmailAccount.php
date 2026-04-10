<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAccount extends Model
{
    protected $fillable = [
        'domain_id', 'email', 'quota', 'status',
        'can_send', 'can_receive', 'max_send_per_hour', 'max_send_per_day',
        'max_send_per_week', 'max_send_per_month',
        'encrypted_password', 'password_preserved',
    ];

    /**
     * encrypted_password holds the plaintext password encrypted with the
     * Laravel app key. Reading the property returns the plaintext.
     * password_preserved = true when the cPanel import re-used the original
     * hash from the backup, in which case the plaintext is unknown but the
     * existing password still works.
     */
    protected function casts(): array
    {
        return [
            'can_send'           => 'boolean',
            'can_receive'        => 'boolean',
            'encrypted_password' => 'encrypted',
            'password_preserved' => 'boolean',
        ];
    }

    /**
     * Hide the encrypted password from default array/JSON serialization so
     * it never leaks via API responses or activity logs.
     */
    protected $hidden = ['encrypted_password'];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
