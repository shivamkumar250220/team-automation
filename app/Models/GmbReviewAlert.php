<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GmbReviewAlert extends Model
{
    protected $table = 'gmb_review_alerts';

    protected $fillable = [
        'client_id',
        'gmb_location_id',
        'gmb_review_id',
        'review_id',
        'reviewer_name',
        'rating',
        'comment',
        'draft_reply',
        'alert_sent_at',
    ];

    protected $casts = [
        'alert_sent_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(GmbLocation::class, 'gmb_location_id');
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(GmbReview::class, 'gmb_review_id');
    }
}