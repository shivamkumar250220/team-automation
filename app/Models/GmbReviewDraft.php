<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GmbReviewDraft extends Model
{
    protected $fillable = [
        'gmb_review_id',
        'draft_1',
        'draft_2',
        'draft_3',
        'draft_4',
        'draft_5',
        'selected_draft',
        'final_response',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(GmbReview::class, 'gmb_review_id');
    }

    // Get all drafts as array
    public function getDraftsArray(): array
    {
        return array_filter([
            1 => $this->draft_1,
            2 => $this->draft_2,
            3 => $this->draft_3,
            4 => $this->draft_4,
            5 => $this->draft_5,
        ]);
    }
}