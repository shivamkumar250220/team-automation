<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GmbReview extends Model
{
    protected $fillable = [
        'gmb_location_id',
        'client_id', 
        'review_id',
        'reviewer_name',
        'reviewer_photo',
        'rating',
        'comment',
        'review_time',
        'reply_text',
        'reply_time',
        'reply_posted',
    ];

    protected $casts = [
        'review_time'  => 'datetime',
        'reply_time'   => 'datetime',
        'reply_posted' => 'boolean',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(GmbLocation::class, 'gmb_location_id');
    }

    public function draft(): HasOne
    {
        return $this->hasOne(GmbReviewDraft::class);
    }

    // Star count helper
    public function starCount(): int
    {
        return match ($this->rating) {
            'ONE'   => 1,
            'TWO'   => 2,
            'THREE' => 3,
            'FOUR'  => 4,
            'FIVE'  => 5,
            default => 0,
        };
    }

    public function isNegative(): bool
    {
        return in_array($this->rating, ['ONE', 'TWO']);
    }
}