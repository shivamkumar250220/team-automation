<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GmbCitationAudit extends Model
{
    protected $table = 'gmb_citation_audits';

    protected $fillable = [
        'client_id',
        'gmb_location_id',
        'directory',
        'gbp_name',
        'gbp_address',
        'gbp_phone',
        'found_name',
        'found_address',
        'found_phone',
        'status',
        'scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(GmbLocation::class, 'gmb_location_id');
    }
}