<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NodeApp extends Model
{
    protected $fillable = [
        'server_id', 'account_id', 'domain_id',
        'name', 'startup_command', 'working_dir', 'port', 'status',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /** The PM2 process name used on the server: {username}_{name} */
    public function pm2Name(): string
    {
        return $this->account->username . '_' . $this->name;
    }
}
