@extends('layouts.app')

@section('title', 'Hygiene Sheet — ' . ($group['display_url'] ?? ''))
@section('page_header', 'Hygiene Sheet')
@section('page_icon', 'mdi mdi-magnify')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('seo.dashboard', [$created_by_user_id, $client_property_id]) }}">SEO Tools</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Hygiene Sheet</li>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
    :root {
        --hs-bg:          #0f1117;
        --hs-surface:     #16191f;
        --hs-surface-2:   #1c2029;
        --hs-border:      #2a2f3d;
        --hs-accent:      #4f7cff;
        --hs-accent-glow: rgba(79,124,255,.18);
        --hs-green:       #22c55e;
        --hs-orange:      #f59e0b;
        --hs-red:         #ef4444;
        --hs-yellow:      #eab308;
        --hs-text:        #e2e8f0;
        --hs-muted:       #64748b;
        --hs-font:        'DM Sans', sans-serif;
        --hs-mono:        'DM Mono', monospace;
        --hs-radius:      10px;
        --hs-radius-lg:   16px;
        --transition:     .22s cubic-bezier(.4,0,.2,1);
    }

    /* ── Page shell ─────────────────────────────────────────── */
    .hs-wrapper {
        font-family: var(--hs-font);
        color: var(--hs-text);
        padding: 0;
    }

    /* ── Top bar ─────────────────────────────────────────────── */
    .hs-topbar {
        background: var(--hs-surface);
        border: 1px solid var(--hs-border);
        border-radius: var(--hs-radius-lg);
        padding: 20px 28px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .hs-topbar-left {
        display: flex;
        align-items: center;
        gap: 14px;
    }
    .hs-domain-badge {
        background: var(--hs-surface-2);
        border: 1px solid var(--hs-border);
        border-radius: 8px;
        padding: 8px 16px;
        font-family: var(--hs-mono);
        font-size: 13px;
        color: var(--hs-accent);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .hs-domain-badge i { font-size: 15px; }
    .hs-run-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--hs-accent);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px 20px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
    }
    .hs-run-btn:hover { background: #3b6bff; box-shadow: 0 0 20px var(--hs-accent-glow); }
    .hs-run-btn:disabled { opacity: .5; cursor: not-allowed; }
    .hs-run-btn .spinner {
        width: 15px; height: 15px;
        border: 2px solid rgba(255,255,255,.3);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin .7s linear infinite;
        display: none;
    }
    .hs-run-btn.loading .spinner { display: block; }
    .hs-run-btn.loading .btn-icon { display: none; }

    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Tab nav ─────────────────────────────────────────────── */
    .hs-tab-nav {
        display: flex;
        gap: 4px;
        background: var(--hs-surface);
        border: 1px solid var(--hs-border);
        border-radius: var(--hs-radius-lg);
        padding: 6px;
        margin-bottom: 24px;
        width: fit-content;
    }
    .hs-tab-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 22px;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: var(--hs-muted);
        font-family: var(--hs-font);
        font-size: 13.5px;
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        position: relative;
    }
    .hs-tab-btn:hover { color: var(--hs-text); background: var(--hs-surface-2); }
    .hs-tab-btn.active {
        background: var(--hs-accent);
        color: #fff;
        box-shadow: 0 2px 14px var(--hs-accent-glow);
    }
    .hs-tab-btn .tab-badge {
        font-size: 10px;
        background: rgba(255,255,255,.2);
        border-radius: 4px;
        padding: 1px 5px;
        font-weight: 600;
    }

    /* ── Tab panels ─────────────────────────────────────────── */
    .hs-tab-panel { display: none; }
    .hs-tab-panel.active { display: block; animation: fadeUp .3s ease; }
    @keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }

    /* ── Empty / loading state ──────────────────────────────── */
    .hs-empty {
        text-align: center;
        padding: 80px 40px;
        background: var(--hs-surface);
        border: 1px dashed var(--hs-border);
        border-radius: var(--hs-radius-lg);
    }
    .hs-empty-icon { font-size: 52px; color: var(--hs-muted); margin-bottom: 16px; }
    .hs-empty h3 { font-size: 18px; font-weight: 600; margin-bottom: 8px; }
    .hs-empty p { color: var(--hs-muted); font-size: 14px; margin: 0; }

    .hs-skeleton {
        background: linear-gradient(90deg, var(--hs-surface) 25%, var(--hs-surface-2) 50%, var(--hs-surface) 75%);
        background-size: 200% 100%;
        animation: shimmer 1.4s infinite;
        border-radius: 6px;
    }
    @keyframes shimmer { to { background-position: -200% 0; } }

    /* ── Cards ──────────────────────────────────────────────── */
    .hs-card {
        background: var(--hs-surface);
        border: 1px solid var(--hs-border);
        border-radius: var(--hs-radius-lg);
        overflow: hidden;
        margin-bottom: 20px;
    }
    .hs-card-header {
        padding: 16px 22px;
        border-bottom: 1px solid var(--hs-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .hs-card-title {
        font-size: 14px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .hs-card-body { padding: 20px 22px; }

    /* ── Score ring ─────────────────────────────────────────── */
    .score-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .score-card {
        background: var(--hs-surface-2);
        border: 1px solid var(--hs-border);
        border-radius: var(--hs-radius);
        padding: 18px 14px;
        text-align: center;
        transition: var(--transition);
    }
    .score-card:hover { border-color: var(--hs-accent); transform: translateY(-2px); }
    .score-ring {
        position: relative;
        width: 76px;
        height: 76px;
        margin: 0 auto 10px;
    }
    .score-ring svg { transform: rotate(-90deg); }
    .score-ring circle { fill: none; stroke-width: 6; stroke-linecap: round; }
    .score-ring .track { stroke: var(--hs-border); }
    .score-ring .fill { stroke-dasharray: 201; stroke-dashoffset: 201; transition: stroke-dashoffset 1s ease; }
    .score-number {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        font-size: 18px;
        font-weight: 700;
        font-family: var(--hs-mono);
    }
    .score-label { font-size: 11px; font-weight: 600; color: var(--hs-muted); text-transform: uppercase; letter-spacing: .6px; }
    .score-good  { color: var(--hs-green); }
    .score-avg   { color: var(--hs-orange); }
    .score-bad   { color: var(--hs-red); }

    /* ── Metrics table ──────────────────────────────────────── */
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
    }
    .metric-item {
        background: var(--hs-surface-2);
        border: 1px solid var(--hs-border);
        border-radius: var(--hs-radius);
        padding: 14px 16px;
    }
    .metric-name { font-size: 11px; color: var(--hs-muted); font-weight: 500; margin-bottom: 6px; text-transform: uppercase; letter-spacing: .5px; }
    .metric-val  { font-size: 20px; font-weight: 700; font-family: var(--hs-mono); }
    .metric-good  { color: var(--hs-green); }
    .metric-avg   { color: var(--hs-orange); }
    .metric-bad   { color: var(--hs-red); }
    .metric-neutral { color: var(--hs-text); }

    /* ── Strategy switcher (Desktop / Mobile) ───────────────── */
    .strategy-tabs {
        display: flex;
        gap: 4px;
        background: var(--hs-surface-2);
        border-radius: 8px;
        padding: 4px;
    }
    .strategy-tab {
        flex: 1;
        text-align: center;
        padding: 6px 16px;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        border: none;
        background: transparent;
        color: var(--hs-muted);
        font-family: var(--hs-font);
        transition: var(--transition);
    }
    .strategy-tab.active { background: var(--hs-accent); color: #fff; }

    /* ── Observatory / Mozilla ──────────────────────────────── */
    .obs-grade-wrap {
        display: flex;
        align-items: center;
        gap: 28px;
        padding: 24px;
        background: var(--hs-surface-2);
        border: 1px solid var(--hs-border);
        border-radius: var(--hs-radius-lg);
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .obs-grade-circle {
        width: 100px; height: 100px;
        border-radius: 50%;
        border: 4px solid;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .obs-grade-letter { font-size: 34px; font-weight: 800; line-height: 1; }
    .obs-grade-label  { font-size: 10px; color: var(--hs-muted); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
    .obs-grade-meta { flex: 1; min-width: 180px; }
    .obs-grade-meta h2 { font-size: 22px; font-weight: 700; margin: 0 0 6px; }
    .obs-grade-meta .obs-sub { font-size: 13px; color: var(--hs-muted); }
    .obs-score-big { font-size: 38px; font-weight: 800; font-family: var(--hs-mono); margin-right: 20px; }

    .obs-grade-A  { border-color: var(--hs-green);  color: var(--hs-green); }
    .obs-grade-B  { border-color: #86efac;           color: #86efac; }
    .obs-grade-C  { border-color: var(--hs-yellow);  color: var(--hs-yellow); }
    .obs-grade-D  { border-color: var(--hs-orange);  color: var(--hs-orange); }
    .obs-grade-F  { border-color: var(--hs-red);     color: var(--hs-red); }

    /* ── Test results table (Observatory) ───────────────────── */
    .obs-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 13px;
    }
    .obs-table thead tr th {
        background: var(--hs-surface-2);
        padding: 11px 16px;
        text-align: left;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .6px;
        color: var(--hs-muted);
        border-bottom: 1px solid var(--hs-border);
    }
    .obs-table thead tr th:first-child { border-radius: 8px 0 0 0; }
    .obs-table thead tr th:last-child  { border-radius: 0 8px 0 0; }
    .obs-table tbody tr { transition: background var(--transition); }
    .obs-table tbody tr:hover td { background: var(--hs-surface-2); }
    .obs-table tbody td {
        padding: 11px 16px;
        border-bottom: 1px solid var(--hs-border);
        vertical-align: middle;
    }
    .obs-table tbody tr:last-child td { border-bottom: none; }

    .pill {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
    }
    .pill-pass { background: rgba(34,197,94,.12); color: var(--hs-green); }
    .pill-fail { background: rgba(239,68,68,.12);  color: var(--hs-red); }
    .pill-info { background: rgba(79,124,255,.12); color: var(--hs-accent); }
    .pill-warn { background: rgba(245,158,11,.12); color: var(--hs-orange); }

    /* ── Security header row expand ─────────────────────────── */
    .obs-row-toggle { cursor: pointer; }
    .obs-row-toggle td:first-child { padding-left: 10px; }
    .obs-detail-row td { padding: 0 16px; }
    .obs-detail-inner {
        background: var(--hs-surface-2);
        border-radius: 8px;
        padding: 12px 14px;
        margin: 6px 0;
        font-family: var(--hs-mono);
        font-size: 12px;
        color: var(--hs-muted);
        word-break: break-all;
    }

    /* ── Page Speed breakdown table ─────────────────────────── */
    .psi-opportunities { margin-top: 20px; }
    .psi-opp-item {
        background: var(--hs-surface-2);
        border: 1px solid var(--hs-border);
        border-radius: var(--hs-radius);
        padding: 14px 18px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        cursor: pointer;
        transition: var(--transition);
    }
    .psi-opp-item:hover { border-color: var(--hs-accent); }
    .psi-opp-title { font-size: 13.5px; font-weight: 500; }
    .psi-opp-savings {
        font-size: 12px;
        font-family: var(--hs-mono);
        color: var(--hs-orange);
        white-space: nowrap;
        font-weight: 600;
    }
    .psi-opp-score-dot {
        width: 10px; height: 10px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    /* ── CrUX field data bars ───────────────────────────────── */
    .crux-bar-wrap { margin-top: 16px; }
    .crux-bar-row { margin-bottom: 14px; }
    .crux-bar-label {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 5px; font-size: 12px; font-weight: 600;
    }
    .crux-bar-label .crux-pctile { font-family: var(--hs-mono); color: var(--hs-muted); }
    .crux-bar {
        height: 8px; border-radius: 8px;
        background: var(--hs-border);
        overflow: hidden; display: flex;
    }
    .crux-bar-seg { height: 100%; transition: width 1s ease; }
    .crux-bar-legend { display: flex; gap: 14px; margin-top: 6px; font-size: 11px; color: var(--hs-muted); }
    .crux-bar-legend span { display: flex; align-items: center; gap: 5px; }
    .crux-dot { width: 8px; height: 8px; border-radius: 50%; }

    /* ── Field data category pill ───────────────────────────── */
    .crux-cat-good   { color: var(--hs-green); }
    .crux-cat-needs  { color: var(--hs-orange); }
    .crux-cat-poor   { color: var(--hs-red); }

    /* ── Alert / error banner ───────────────────────────────── */
    .hs-alert {
        border-radius: var(--hs-radius);
        padding: 14px 18px;
        font-size: 13.5px;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 20px;
    }
    .hs-alert-danger { background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.25); color: #fca5a5; }
    .hs-alert-info   { background: rgba(79,124,255,.08); border: 1px solid rgba(79,124,255,.25); color: #93c5fd; }

    /* ── Responsive ─────────────────────────────────────────── */
    @media (max-width: 640px) {
        .hs-topbar { flex-direction: column; align-items: flex-start; }
        .hs-tab-nav { width: 100%; overflow-x: auto; }
        .score-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>
@endpush

@section('content')
<div class="hs-wrapper">

    {{-- ── Top bar ─────────────────────────────────────────── --}}
    <div class="hs-topbar">
        <div class="hs-topbar-left">
            <div class="hs-domain-badge">
                <i class="ri-global-line"></i>
                <span id="displayDomain">{{ $group['display_url'] ?? 'No domain set' }}</span>
            </div> 
        </div>
        <button class="hs-run-btn" id="runAnalysisBtn" onclick="runFullAnalysis()">
            <div class="spinner"></div>
            <i class="ri-refresh-line btn-icon"></i>
            Run Analysis
        </button>
    </div>

    {{-- ── Tab Navigation ──────────────────────────────────── --}}
    <div class="hs-tab-nav">
        <button class="hs-tab-btn active" onclick="switchTab('mozilla', this)">
            <i class="ri-shield-check-line"></i>
            Mozilla Observatory
            <span class="tab-badge" id="mozBadge">—</span>
        </button>
        <button class="hs-tab-btn" onclick="switchTab('pagespeed', this)">
            <i class="ri-speed-up-line"></i>
            Page Speed
            <span class="tab-badge" id="psBadge">—</span>
        </button>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         TAB 1 — Mozilla Observatory
    ══════════════════════════════════════════════════════════════ --}}
    <div class="hs-tab-panel active" id="panel-mozilla">

        {{-- Empty state --}}
        <div class="hs-empty" id="mozEmpty">
            <div class="hs-empty-icon"><i class="ri-shield-line"></i></div>
            <h3>No Observatory data yet</h3>
            <p>Click <strong>Run Analysis</strong> to scan your domain's security headers via Mozilla Observatory.</p>
        </div>

        {{-- Error state --}}
        <div class="hs-alert hs-alert-danger" id="mozError" style="display:none">
            <i class="ri-error-warning-line"></i>
            <span id="mozErrorMsg">An error occurred during the Observatory scan.</span>
        </div>

        {{-- Results --}}
        <div id="mozResults" style="display:none">

            {{-- Grade + Score hero --}}
            <div class="obs-grade-wrap" id="mozGradeWrap">
                <div class="obs-grade-circle" id="mozGradeCircle">
                    <span class="obs-grade-letter" id="mozGradeLetter">—</span>
                    <span class="obs-grade-label">Grade</span>
                </div>
                <div class="obs-score-big" id="mozScoreBig">—</div>
                <div class="obs-grade-meta">
                    <h2 id="mozScoreLabel">Security Score</h2>
                    <div class="obs-sub">
                        <span id="mozPassCount">0</span> tests passed &nbsp;·&nbsp;
                        <span id="mozFailCount">0</span> tests failed &nbsp;·&nbsp;
                        <span id="mozScanTime">—</span>
                    </div>
                </div>
            </div>

            {{-- Tests table --}}
            <div class="hs-card">
                <div class="hs-card-header">
                    <span class="hs-card-title"><i class="ri-list-check-2"></i> Security Header Tests</span>
                    <div style="display:flex;gap:8px;font-size:12px;">
                        <span class="pill pill-pass"><i class="ri-checkbox-circle-fill"></i> Pass</span>
                        <span class="pill pill-fail"><i class="ri-close-circle-fill"></i> Fail</span>
                        <span class="pill pill-info"><i class="ri-information-fill"></i> Info</span>
                    </div>
                </div>
                <div class="hs-card-body" style="padding:0">
                    <table class="obs-table" id="mozTestsTable">
                        <thead>
                            <tr>
                                <th style="width:36px"></th>
                                <th>Test</th>
                                <th>Result</th>
                                <th style="text-align:right">Score Impact</th>
                                <th style="text-align:center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="mozTestsTbody">
                        </tbody>
                    </table>
                </div>
            </div>

        </div>{{-- /mozResults --}}

    </div>{{-- /panel-mozilla --}}


    {{-- ══════════════════════════════════════════════════════════
         TAB 2 — Page Speed
    ══════════════════════════════════════════════════════════════ --}}
    <div class="hs-tab-panel" id="panel-pagespeed">

        {{-- Empty state --}}
        <div class="hs-empty" id="psEmpty">
            <div class="hs-empty-icon"><i class="ri-speed-up-line"></i></div>
            <h3>No PageSpeed data yet</h3>
            <p>Click <strong>Run Analysis</strong> to fetch Google PageSpeed Insights for Desktop & Mobile.</p>
        </div>

        {{-- Error state --}}
        <div class="hs-alert hs-alert-danger" id="psError" style="display:none">
            <i class="ri-error-warning-line"></i>
            <span id="psErrorMsg">An error occurred fetching PageSpeed data.</span>
        </div>

        {{-- Results --}}
        <div id="psResults" style="display:none">

            {{-- Strategy switcher --}}
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
                <div class="strategy-tabs">
                    <button class="strategy-tab active" id="stDesktop" onclick="switchStrategy('desktop')">
                        <i class="ri-computer-line"></i> Desktop
                    </button>
                    <button class="strategy-tab" id="stMobile" onclick="switchStrategy('mobile')">
                        <i class="ri-smartphone-line"></i> Mobile
                    </button>
                </div>
                <a id="psReportLink" href="#" target="_blank" rel="noopener"
                   style="font-size:12.5px;color:var(--hs-accent);text-decoration:none;display:flex;align-items:center;gap:5px;">
                    <i class="ri-external-link-line"></i> Open full PageSpeed report
                </a>
            </div>

            {{-- Category scores --}}
            <div class="score-grid" id="psScoreGrid"></div>

            {{-- Core Web Vitals --}}
            <div class="hs-card" id="psCwvCard">
                <div class="hs-card-header">
                    <span class="hs-card-title"><i class="ri-pulse-line"></i> Core Web Vitals</span>
                </div>
                <div class="hs-card-body">
                    <div class="metrics-grid" id="psCwvGrid"></div>
                </div>
            </div>

            {{-- CrUX Field Data --}}
            <div class="hs-card" id="psCruxCard">
                <div class="hs-card-header">
                    <span class="hs-card-title"><i class="ri-bar-chart-grouped-line"></i> Real-User Field Data (CrUX)</span>
                    <span style="font-size:12px;color:var(--hs-muted)">75th-percentile experience</span>
                </div>
                <div class="hs-card-body">
                    <div class="crux-bar-wrap" id="psCruxBars"></div>
                </div>
            </div>

            {{-- Opportunities --}}
            <div class="hs-card">
                <div class="hs-card-header">
                    <span class="hs-card-title"><i class="ri-lightbulb-line"></i> Opportunities</span>
                </div>
                <div class="hs-card-body">
                    <div class="psi-opportunities" id="psOpportunities"></div>
                </div>
            </div>

            {{-- Diagnostics --}}
            <div class="hs-card">
                <div class="hs-card-header">
                    <span class="hs-card-title"><i class="ri-stethoscope-line"></i> Diagnostics</span>
                </div>
                <div class="hs-card-body">
                    <div id="psDiagnostics"></div>
                </div>
            </div>

        </div>{{-- /psResults --}}

    </div>{{-- /panel-pagespeed --}}

</div>{{-- /hs-wrapper --}}
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
/* ============================================================
   GLOBALS
   ============================================================ */
const DOMAIN       = @json($group['display_url'] ?? '');
const CREATED_BY   = @json($created_by_user_id);
const CLIENT_PROP  = @json($client_property_id);
const CSRF         = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

let psData  = null;   // full PageSpeed response { desktop:{...}, mobile:{...} }
let mozData = null;   // full Observatory response
let currentStrategy = 'desktop';

/* ============================================================
   TAB SWITCHING
   ============================================================ */
function switchTab(tab, btn) {
    document.querySelectorAll('.hs-tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.hs-tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');
    btn.classList.add('active');
}

/* ============================================================
   RUN FULL ANALYSIS
   ============================================================ */
async function runFullAnalysis() {
    const btn = document.getElementById('runAnalysisBtn');
    btn.disabled = true;
    btn.classList.add('loading');

    // Kick off both in parallel
    await Promise.allSettled([
        fetchObservatory(),
        fetchPageSpeed(),
    ]);

    btn.disabled = false;
    btn.classList.remove('loading');
}

/* ============================================================
   MOZILLA OBSERVATORY
   ============================================================ */
async function fetchObservatory() {
    hideEl('mozEmpty'); hideEl('mozError'); hideEl('mozResults');

    try {
        const res = await fetch(
            `/seo/${CREATED_BY}/${CLIENT_PROP}/hygiene/observatory`,
            { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
              body: JSON.stringify({ domain: DOMAIN }) }
        );
        const json = await res.json();

        if (!res.ok || json.error) {
            showError('moz', json.message ?? 'Observatory scan failed.');
            return;
        }

        mozData = json;
        renderObservatory(json);

    } catch (e) {
        showError('moz', 'Network error: ' + e.message);
    }
}

function renderObservatory(data) {
    const grade = data.grade ?? '?';
    const score = data.score ?? 0;
    const tests = data.tests ?? {};

    // Badge
    document.getElementById('mozBadge').textContent = grade;

    // Grade circle
    const circle = document.getElementById('mozGradeCircle');
    circle.className = 'obs-grade-circle obs-grade-' + grade.charAt(0);
    document.getElementById('mozGradeLetter').textContent = grade;
    document.getElementById('mozScoreBig').textContent = score + '/100';

    // Pass / fail counts
    const testArr = Object.values(tests);
    const passed  = testArr.filter(t => t.pass).length;
    const failed  = testArr.filter(t => !t.pass).length;

    document.getElementById('mozPassCount').textContent = passed;
    document.getElementById('mozFailCount').textContent = failed;
    document.getElementById('mozScanTime').textContent  =
        data.end_time ? new Date(data.end_time * 1000).toLocaleString() : 'just now';

    // Tests table
    const tbody = document.getElementById('mozTestsTbody');
    tbody.innerHTML = '';

    Object.entries(tests).forEach(([key, t]) => {
        const passed = t.pass;
        const pill   = passed ? 'pill-pass' : (t.score_modifier < 0 ? 'pill-fail' : 'pill-info');
        const icon   = passed ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill';
        const impact = t.score_modifier > 0 ? '+' + t.score_modifier : t.score_modifier;
        const detailId = 'obs-det-' + key.replace(/[^a-z0-9]/gi, '-');

        const headerValue = Array.isArray(t.result) ? t.result.join('<br>') : (t.result ?? '—');

        const tr = document.createElement('tr');
        tr.className = 'obs-row-toggle';
        tr.onclick   = () => toggleDetail(detailId);
        tr.innerHTML = `
            <td><i class="ri-arrow-right-s-line" style="color:var(--hs-muted);font-size:16px;" id="arr-${detailId}"></i></td>
            <td>
                <div style="font-weight:600;font-size:13px;">${t.name ?? key}</div>
                <div style="font-size:11px;color:var(--hs-muted);margin-top:2px;">${key}</div>
            </td>
            <td style="font-family:var(--hs-mono);font-size:12px;color:var(--hs-muted);max-width:260px;word-break:break-all;">${headerValue}</td>
            <td style="text-align:right;font-family:var(--hs-mono);font-weight:700;font-size:13px;color:${impact > 0 ? 'var(--hs-green)' : (impact < 0 ? 'var(--hs-red)' : 'var(--hs-muted)') }">${impact ?? '0'}</td>
            <td style="text-align:center"><span class="pill ${pill}"><i class="${icon}"></i> ${passed ? 'Pass' : 'Fail'}</span></td>
        `;
        tbody.appendChild(tr);

        // Detail expansion row
        const description = t.description ?? '';
        const detRow = document.createElement('tr');
        detRow.className = 'obs-detail-row';
        detRow.id = detailId;
        detRow.style.display = 'none';
        detRow.innerHTML = `<td colspan="5">
            <div class="obs-detail-inner">${description || 'No additional details available.'}</div>
        </td>`;
        tbody.appendChild(detRow);
    });

    showEl('mozResults');
}

function toggleDetail(id) {
    const el  = document.getElementById(id);
    const arr = document.getElementById('arr-' + id);
    if (!el) return;
    const visible = el.style.display !== 'none';
    el.style.display  = visible ? 'none' : 'table-row';
    if (arr) arr.style.transform = visible ? '' : 'rotate(90deg)';
}

/* ============================================================
   PAGE SPEED INSIGHTS
   ============================================================ */
async function fetchPageSpeed() {
    hideEl('psEmpty'); hideEl('psError'); hideEl('psResults');

    try {
        const res = await fetch(
            `/seo/${CREATED_BY}/${CLIENT_PROP}/hygiene/pagespeed`,
            { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' },
              body: JSON.stringify({ domain: DOMAIN }) }
        );
        const json = await res.json();

        if (!res.ok || json.error) {
            showError('ps', json.message ?? 'PageSpeed fetch failed.');
            return;
        }

        psData = json;
        renderPageSpeed('desktop');

        // update badge with performance score
        const perf = json?.desktop?.category_scores?.performance?.score ?? json?.mobile?.category_scores?.performance?.score ?? '—';
        document.getElementById('psBadge').textContent = perf;

    } catch (e) {
        showError('ps', 'Network error: ' + e.message);
    }
}

function switchStrategy(strategy) {
    currentStrategy = strategy;
    document.getElementById('stDesktop').classList.toggle('active', strategy === 'desktop');
    document.getElementById('stMobile').classList.toggle('active',  strategy === 'mobile');
    if (psData) renderPageSpeed(strategy);
}

function renderPageSpeed(strategy) {
    const d = psData?.[strategy];
    if (!d) { showError('ps', 'No data for strategy: ' + strategy); return; }

    // PageSpeed report link
    const reportUrl = `https://pagespeed.web.dev/report?url=${encodeURIComponent(DOMAIN)}&form_factor=${strategy}`;
    document.getElementById('psReportLink').href = reportUrl;

    // ── Category scores ────────────────────────────────────────
    const scoreGrid = document.getElementById('psScoreGrid');
    scoreGrid.innerHTML = '';
    const catMeta = { performance:'ri-speed-line', accessibility:'ri-wheelchair-line', 'best-practices':'ri-award-line', seo:'ri-search-2-line' };

    Object.entries(d.category_scores ?? {}).forEach(([key, cat]) => {
        const score = cat.score ?? 0;
        const cls   = score >= 90 ? 'score-good' : score >= 50 ? 'score-avg' : 'score-bad';
        const clr   = score >= 90 ? '#22c55e'    : score >= 50 ? '#f59e0b'   : '#ef4444';
        const dash  = Math.round(201 * (1 - score / 100));
        const icon  = catMeta[key] ?? 'ri-bar-chart-line';

        const card = document.createElement('div');
        card.className = 'score-card';
        card.innerHTML = `
            <div class="score-ring">
                <svg width="76" height="76" viewBox="0 0 76 76">
                    <circle class="track" cx="38" cy="38" r="32"/>
                    <circle class="fill" cx="38" cy="38" r="32"
                        stroke="${clr}"
                        style="stroke-dashoffset:${dash}"
                        data-dashoffset="${dash}"/>
                </svg>
                <div class="score-number ${cls}">${score}</div>
            </div>
            <div class="score-label"><i class="${icon}"></i> ${cat.title ?? key}</div>
        `;
        scoreGrid.appendChild(card);
    });

    // ── Core Web Vitals ────────────────────────────────────────
    const cwvGrid = document.getElementById('psCwvGrid');
    cwvGrid.innerHTML = '';
    const cwv = d.core_web_vitals ?? {};

    const cwvThresholds = {
        first_contentful_paint:    { good: 1800, poor: 3000 },
        largest_contentful_paint:  { good: 2500, poor: 4000 },
        total_blocking_time:       { good: 200,  poor: 600  },
        cumulative_layout_shift:   { good: 0.1,  poor: 0.25 },
        speed_index:               { good: 3400, poor: 5800 },
        time_to_interactive:       { good: 3800, poor: 7300 },
        time_to_first_byte:        { good: 800,  poor: 1800 },
        interaction_to_next_paint: { good: 200,  poor: 500  },
    };
    const cwvLabels = {
        first_contentful_paint:    'First Contentful Paint',
        largest_contentful_paint:  'Largest Contentful Paint',
        total_blocking_time:       'Total Blocking Time',
        cumulative_layout_shift:   'Cumulative Layout Shift',
        speed_index:               'Speed Index',
        time_to_interactive:       'Time to Interactive',
        time_to_first_byte:        'Time to First Byte',
        interaction_to_next_paint: 'Interaction to Next Paint',
    };

    Object.entries(cwv).forEach(([key, metric]) => {
        const val    = metric.display_value ?? metric.display ?? '—';
        const num    = metric.numeric_value ?? metric.value;
        const thresh = cwvThresholds[key];
        let cls = 'metric-neutral';
        if (thresh && num !== null && num !== undefined) {
            cls = num <= thresh.good ? 'metric-good' : num <= thresh.poor ? 'metric-avg' : 'metric-bad';
        }
        const item = document.createElement('div');
        item.className = 'metric-item';
        item.innerHTML = `<div class="metric-name">${cwvLabels[key] ?? key}</div>
                          <div class="metric-val ${cls}">${val}</div>`;
        cwvGrid.appendChild(item);
    });

    // ── CrUX field data ────────────────────────────────────────
    const cruxBars  = document.getElementById('psCruxBars');
    cruxBars.innerHTML = '';
    const fieldData = d.field_data_crux ?? {};

    const cruxLabels = {
        FIRST_CONTENTFUL_PAINT_MS:    'First Contentful Paint',
        LARGEST_CONTENTFUL_PAINT_MS:  'Largest Contentful Paint',
        CUMULATIVE_LAYOUT_SHIFT_SCORE:'Cumulative Layout Shift',
        INTERACTION_TO_NEXT_PAINT:    'Interaction to Next Paint',
        EXPERIMENTAL_TIME_TO_FIRST_BYTE: 'Time to First Byte',
    };
    const cruxColors = ['#22c55e','#f59e0b','#ef4444'];

    if (Object.keys(fieldData).length === 0) {
        cruxBars.innerHTML = '<p style="color:var(--hs-muted);font-size:13px;">No real-user field data available for this URL.</p>';
    } else {
        Object.entries(fieldData).forEach(([key, metric]) => {
            const label  = cruxLabels[key] ?? key;
            const pctile = metric.percentile ?? 'N/A';
            const dists  = metric.distributions ?? [];
            const cat    = metric.category ?? '';
            const catCls = cat === 'FAST' ? 'crux-cat-good' : cat === 'AVERAGE' ? 'crux-cat-needs' : cat === 'SLOW' ? 'crux-cat-poor' : '';

            const wrap = document.createElement('div');
            wrap.className = 'crux-bar-row';

            const segs = dists.map((d, i) => {
                const pct = Math.round((d.proportion ?? 0) * 100);
                return `<div class="crux-bar-seg" style="width:${pct}%;background:${cruxColors[i] ?? '#4f7cff'}"></div>`;
            }).join('');

            const legend = dists.map((d, i) => {
                const pct   = Math.round((d.proportion ?? 0) * 100);
                const names = ['Good', 'Needs Improvement', 'Poor'];
                return `<span><span class="crux-dot" style="background:${cruxColors[i] ?? '#4f7cff'}"></span>${names[i] ?? ''}: ${pct}%</span>`;
            }).join('');

            wrap.innerHTML = `
                <div class="crux-bar-label">
                    <span>${label} <span class="${catCls}" style="margin-left:6px;font-size:11px;">${cat}</span></span>
                    <span class="crux-pctile">p75: ${pctile}</span>
                </div>
                <div class="crux-bar">${segs}</div>
                <div class="crux-bar-legend">${legend}</div>
            `;
            cruxBars.appendChild(wrap);
        });
    }

    // ── Opportunities ──────────────────────────────────────────
    const oppEl = document.getElementById('psOpportunities');
    oppEl.innerHTML = '';
    const opps = d.opportunities ?? {};

    if (Object.keys(opps).length === 0) {
        oppEl.innerHTML = '<p style="color:var(--hs-muted);font-size:13px;">No opportunities detected.</p>';
    } else {
        Object.entries(opps).forEach(([key, opp]) => {
            const savings = opp.savings_ms
                ? `Save ~${Math.round(opp.savings_ms)} ms`
                : (opp.display_value ?? '');
            const score   = opp.score ?? 0;
            const dotColor = score >= .9 ? 'var(--hs-green)' : score >= .5 ? 'var(--hs-orange)' : 'var(--hs-red)';

            const item = document.createElement('div');
            item.className = 'psi-opp-item';
            item.title     = opp.description ?? '';
            item.innerHTML = `
                <span class="psi-opp-score-dot" style="background:${dotColor}"></span>
                <span class="psi-opp-title">${opp.title ?? key}</span>
                <span class="psi-opp-savings">${savings}</span>
            `;
            oppEl.appendChild(item);
        });
    }

    // ── Diagnostics ────────────────────────────────────────────
    const diagEl = document.getElementById('psDiagnostics');
    diagEl.innerHTML = '';
    const diags = d.diagnostics ?? {};

    if (Object.keys(diags).length === 0) {
        diagEl.innerHTML = '<p style="color:var(--hs-muted);font-size:13px;">No diagnostic items.</p>';
    } else {
        Object.entries(diags).forEach(([key, diag]) => {
            const score   = diag.score ?? 1;
            const pill    = score === 1 ? 'pill-pass' : score === null ? 'pill-info' : score >= .5 ? 'pill-warn' : 'pill-fail';
            const status  = score === 1 ? 'Pass' : score === null ? 'Info' : score >= .5 ? 'Warn' : 'Fail';
            const val     = diag.display_value ?? '';

            const item = document.createElement('div');
            item.className = 'psi-opp-item';
            item.title     = diag.description ?? '';
            item.innerHTML = `
                <span class="psi-opp-title">${diag.title ?? key}</span>
                <span style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:12px;color:var(--hs-muted);font-family:var(--hs-mono);">${val}</span>
                    <span class="pill ${pill}">${status}</span>
                </span>
            `;
            diagEl.appendChild(item);
        });
    }

    showEl('psResults');
}

/* ============================================================
   UTILITIES
   ============================================================ */
function showEl(id) { const el = document.getElementById(id); if (el) el.style.display = ''; }
function hideEl(id) { const el = document.getElementById(id); if (el) el.style.display = 'none'; }

function showError(prefix, msg) {
    hideEl(prefix + 'Empty');
    hideEl(prefix + 'Results');
    document.getElementById(prefix + 'ErrorMsg').textContent = msg;
    showEl(prefix + 'Error');
}
</script>
@endpush