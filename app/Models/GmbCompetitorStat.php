<?php
// app/Models/GmbCompetitorStat.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GmbCompetitorStat extends Model
{
    protected $table = 'gmb_competitor_stats';

    protected $fillable = [
        'client_id',
        'gmb_competitor_id',
        'rating',
        'review_count',
        'photo_count',
        'pulled_at',
    ];

    protected $casts = [
        'pulled_at' => 'datetime',
    ];

    public function competitor(): BelongsTo
    {
        return $this->belongsTo(GmbCompetitor::class, 'gmb_competitor_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}