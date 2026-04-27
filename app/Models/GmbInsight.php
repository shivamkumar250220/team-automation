<?php

namespace App\Models;

use App\Models\GmbLocation;
use Illuminate\Database\Eloquent\Model;

class GmbInsight extends Model
{
    protected $table = 'gmb_insights';

    protected $fillable = [
        'gmb_location_id',
        'month',
        'year',
        'views',
        'calls',
        'direction_requests',
        'search_queries',
        'pulled_at',
    ];

    protected $casts = [
        'search_queries' => 'array',
        'pulled_at'      => 'datetime',
    ];

    // ── Relations ──────────────────────────────

    public function location()
    {
        return $this->belongsTo(GmbLocation::class, 'gmb_location_id');
    }

    // ── Scopes ─────────────────────────────────

    public function scopeForMonth($query, int $month, int $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    public function scopeCurrentMonth($query)
    {
        return $query->where('month', now()->month)->where('year', now()->year);
    }
}