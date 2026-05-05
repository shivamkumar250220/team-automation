<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GmbPostTemplate extends Model
{
    protected $fillable = [
        'client_id',
        'gmb_location_id',
        'post_content',
        'topic',
        'emotion',
        'cta',
        'usp',
        'offer',
        'competitors',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(GmbLocation::class, 'gmb_location_id');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }
}