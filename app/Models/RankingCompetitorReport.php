<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RankingCompetitorReport extends Model
{
    protected $fillable = [
        'domainmanagement_id',
        'client_property_id',
        'user_id',
        'location',
        'keywords',
        'client_domain',
        'competitor_option',
        'results_json',
    ];

    protected $casts = [
        'keywords'     => 'array',
        'results_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Human-readable label for the select dropdown.
     * e.g.  "15 Jan 2025 14:30 · Gurgaon · best dermatologist, pigmentation"
     */
    public function getDropdownLabelAttribute(): string
    {
        $date     = $this->created_at->format('d M Y H:i');
        $keywords = implode(', ', array_slice($this->keywords, 0, 3));
        $suffix   = count($this->keywords) > 3 ? ' …' : '';

        return "{$date} · {$this->location} · {$keywords}{$suffix}";
    }
}