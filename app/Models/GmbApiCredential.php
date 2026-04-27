<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Client;

class GmbApiCredential extends Model
{
    protected $table = 'gmb_api_credentials';

    protected $fillable = [
        'client_id',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    // ── Relations ──────────────────────────────

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    // ── Helpers ────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at && now()->greaterThanOrEqualTo($this->expires_at);
    }
}