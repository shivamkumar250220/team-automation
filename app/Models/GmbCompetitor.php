<?php
// app/Models/GmbCompetitor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GmbCompetitor extends Model
{
    protected $table = 'gmb_competitors';

    protected $fillable = [
        'client_id',
        'name',
        'place_id',
        'is_manual',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function stats(): HasMany
    {
        return $this->hasMany(GmbCompetitorStat::class);
    }

    public function latestStat()
    {
        return $this->hasOne(GmbCompetitorStat::class)->latestOfMany();
    }
}