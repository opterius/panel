<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Database extends Model
{
    protected $fillable = [
        'server_id', 'account_id', 'name', 'db_username', 'encrypted_password', 'status',
    ];

    /**
     * The encrypted_password column stores the plaintext DB user password
     * encrypted with the Laravel app key. Reading the property gives back
     * the plaintext; writing stores the encrypted ciphertext automatically.
     */
    protected $casts = [
        'encrypted_password' => 'encrypted',
    ];

    /**
     * Hide the password from default array/JSON serialization so it doesn't
     * accidentally leak via API responses or activity logs.
     */
    protected $hidden = [
        'encrypted_password',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
