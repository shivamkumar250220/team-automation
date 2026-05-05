<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Client;

class GmbLocation extends Model
{
    protected $table = 'gmb_locations';

    protected $fillable = [
        'client_id',
        'location_name',
        'gbp_account_id',
        'gbp_location_id',
        'google_place_id',
        'city',
        'status',
    ];


    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function insights()
    {
        return $this->hasMany(GmbInsight::class);
    }

    public function latestInsight()
    {
        return $this->hasOne(GmbInsight::class)->latestOfMany();
    }

    // ── Scopes ─────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function reviews()
    {
        return $this->hasMany(\App\Models\GmbReview::class, 'gmb_location_id');
    }
}