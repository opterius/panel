<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpAddress extends Model
{
    protected $fillable = ['server_id', 'ip_address', 'type', 'account_id', 'note'];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function isAvailable(): bool
    {
        return $this->type === 'dedicated' && $this->account_id === null;
    }
}
