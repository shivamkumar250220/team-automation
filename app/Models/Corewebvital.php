<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
 
class CoreWebVital extends Model
{
    protected $table = 'core_web_vitals';

    protected $fillable = [
        'created_by_user_id',
        'client_property_id',
        'user_id',
        'url',

        // Mobile
        'mobile_performance_score',
        'mobile_lcp_display',  'mobile_lcp_value',  'mobile_lcp_score',
        'mobile_cls_display',  'mobile_cls_value',  'mobile_cls_score',
        'mobile_tbt_display',  'mobile_tbt_value',  'mobile_tbt_score',
        'mobile_inp_display',  'mobile_inp_value',  'mobile_inp_score',
        'mobile_fcp_display',  'mobile_fcp_value',  'mobile_fcp_score',
        'mobile_ttfb_display', 'mobile_ttfb_value', 'mobile_ttfb_score',
        'mobile_si_display',   'mobile_si_value',   'mobile_si_score',
        'mobile_tti_display',  'mobile_tti_value',  'mobile_tti_score',

        // Desktop
        'desktop_performance_score',
        'desktop_lcp_display',  'desktop_lcp_value',  'desktop_lcp_score',
        'desktop_cls_display',  'desktop_cls_value',  'desktop_cls_score',
        'desktop_tbt_display',  'desktop_tbt_value',  'desktop_tbt_score',
        'desktop_inp_display',  'desktop_inp_value',  'desktop_inp_score',
        'desktop_fcp_display',  'desktop_fcp_value',  'desktop_fcp_score',
        'desktop_ttfb_display', 'desktop_ttfb_value', 'desktop_ttfb_score',
        'desktop_si_display',   'desktop_si_value',   'desktop_si_score',
        'desktop_tti_display',  'desktop_tti_value',  'desktop_tti_score',
    ];

    protected $casts = [
        'mobile_performance_score'  => 'integer',
        'desktop_performance_score' => 'integer',
    ];

    // ── Relationships (add FK constraints in migration if needed) ───

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}