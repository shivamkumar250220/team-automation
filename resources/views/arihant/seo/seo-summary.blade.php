@extends('layouts.app')

@section('title', 'Website Health Audit')

@section('content')

{{-- ═══════════════════════════════════════════════════════════════════════════
     PAGE HEADER
═══════════════════════════════════════════════════════════════════════════ --}}
<div class="wha-page">

    {{-- Breadcrumb / back link --}}
    <div class="wha-breadcrumb">
        <a href="{{ route('seo.dashboard', [$created_by_user_id, $client_property_id]) }}"
           class="wha-back-link">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            SEO Dashboard
        </a>
        <span class="wha-breadcrumb-sep">/</span>
        <span class="wha-breadcrumb-current">Website Health Audit</span>
    </div>

    {{-- Header --}}
    <div class="wha-header">
        <div class="wha-header-left">
            <div class="wha-header-icon">
                <svg width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8"
                     viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M12 8v4l3 3"/>
                </svg>
            </div>
            <div>
                <h1 class="wha-title">Website Health Audit</h1>
                <p class="wha-subtitle">
                    Security headers via Mozilla Observatory &amp; performance via Google PageSpeed Insights
                </p>
            </div>
        </div>

        {{-- Domain badge --}}
        @if($domain)
        <div class="wha-domain-badge">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/>
                <path d="M2 12h20M12 2a15.3 15.3 0 010 20M12 2a15.3 15.3 0 000 20"/>
            </svg>
            {{ $domain }}
        </div>
        @endif
    </div>

    {{-- ═══════ URL INPUT CARD ═══════ --}}
    <div class="wha-card wha-input-card">
        <div class="wha-input-row">
            <div class="wha-input-group">
                <label for="wha-url-input" class="wha-label">Website URL to Audit</label>
                <div class="wha-input-wrap">
                    <span class="wha-input-icon">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
                             viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M2 12h20M12 2a15.3 15.3 0 010 20M12 2a15.3 15.3 0 000 20"/>
                        </svg>
                    </span>
                    <input
                        id="wha-url-input"
                        type="url"
                        class="wha-input"
                        placeholder="https://example.com"
                        value="{{ $domain ? (Str::startsWith($domain, 'http') ? $domain : 'https://'.$domain) : '' }}"
                    >
                </div>
            </div>

            <button id="wha-run-btn" class="wha-run-btn" onclick="runHealthAudit()">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/></svg>
                Run Audit
            </button>
        </div>

        {{-- Progress bar (hidden by default) --}}
        <div id="wha-progress-wrap" class="wha-progress-wrap" style="display:none">
            <div class="wha-progress-bar">
                <div class="wha-progress-fill" id="wha-progress-fill"></div>
            </div>
            <p class="wha-progress-label" id="wha-progress-label">Initialising audit…</p>
        </div>
    </div>

    {{-- ═══════ RESULTS AREA ═══════ --}}
    <div id="wha-results" style="display:none">

        {{-- Meta summary strip --}}
        <div class="wha-meta-strip" id="wha-meta-strip"></div>

        {{-- ── Inner tab bar: Mozilla | PageSpeed ── --}}
        <div class="wha-inner-tabs">
            <button class="wha-inner-tab active" id="tab-mozilla"
                    onclick="switchInnerTab('mozilla')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Mozilla Observatory
            </button>
            <button class="wha-inner-tab" id="tab-pagespeed"
                    onclick="switchInnerTab('pagespeed')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                </svg>
                PageSpeed Insights
            </button>
        </div>

        {{-- ── Mozilla Observatory panel ── --}}
        <div id="panel-mozilla" class="wha-panel">
            <div id="mozilla-content"></div>
        </div>

        {{-- ── PageSpeed Insights panel ── --}}
        <div id="panel-pagespeed" class="wha-panel" style="display:none">
            <div id="pagespeed-content"></div>
        </div>

    </div>{{-- /wha-results --}}

    {{-- ═══════ ERROR ALERT ═══════ --}}
    <div id="wha-error" class="wha-error-box" style="display:none">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"
             viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <span id="wha-error-msg"></span>
    </div>

</div>{{-- /wha-page --}}


{{-- ═══════════════════════════════════════════════════════════════════════════
     STYLES
═══════════════════════════════════════════════════════════════════════════ --}}
<style>
/* ── Variables ───────────────────────────────────────────────────────────── */
:root {
    --wha-primary:   #4f46e5;
    --wha-primary-h: #4338ca;
    --wha-bg:        #f8fafc;
    --wha-card:      #ffffff;
    --wha-border:    #e2e8f0;
    --wha-text:      #1e293b;
    --wha-muted:     #64748b;
    --wha-pass:      #22c55e;
    --wha-fail:      #ef4444;
    --wha-warn:      #f59e0b;
    --wha-info:      #3b82f6;
    --radius:        12px;
    --shadow:        0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
}

/* ── Page shell ─────────────────────────────────────────────────────────── */
.wha-page { max-width: 1280px; margin: 0 auto; padding: 24px 20px 64px; }

/* ── Breadcrumb ─────────────────────────────────────────────────────────── */
.wha-breadcrumb        { display:flex; align-items:center; gap:8px; font-size:.82rem; color:var(--wha-muted); margin-bottom:20px; }
.wha-back-link         { display:flex; align-items:center; gap:5px; color:var(--wha-primary); text-decoration:none; font-weight:500; transition:opacity .15s; }
.wha-back-link:hover   { opacity:.75; }
.wha-breadcrumb-sep    { color:#cbd5e1; }
.wha-breadcrumb-current{ color:var(--wha-text); font-weight:500; }

/* ── Header ─────────────────────────────────────────────────────────────── */
.wha-header        { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:24px; flex-wrap:wrap; }
.wha-header-left   { display:flex; align-items:center; gap:16px; }
.wha-header-icon   { width:52px; height:52px; background:linear-gradient(135deg,#6366f1,#8b5cf6); border-radius:14px;
                     display:flex; align-items:center; justify-content:center; color:#fff; flex-shrink:0; box-shadow:0 4px 14px rgba(99,102,241,.35); }
.wha-title         { font-size:1.55rem; font-weight:700; color:var(--wha-text); margin:0 0 4px; }
.wha-subtitle      { font-size:.85rem; color:var(--wha-muted); margin:0; }
.wha-domain-badge  { display:flex; align-items:center; gap:6px; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;
                     padding:6px 14px; border-radius:99px; font-size:.82rem; font-weight:500; }

/* ── Card ───────────────────────────────────────────────────────────────── */
.wha-card { background:var(--wha-card); border:1px solid var(--wha-border); border-radius:var(--radius); box-shadow:var(--shadow); }

/* ── Input card ─────────────────────────────────────────────────────────── */
.wha-input-card { padding:24px; margin-bottom:24px; }
.wha-input-row  { display:flex; align-items:flex-end; gap:14px; flex-wrap:wrap; }
.wha-input-group{ flex:1; min-width:260px; }
.wha-label      { display:block; font-size:.8rem; font-weight:600; color:var(--wha-text); margin-bottom:8px; }
.wha-input-wrap { position:relative; }
.wha-input-icon { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--wha-muted); pointer-events:none; display:flex; }
.wha-input      { width:100%; padding:10px 14px 10px 38px; border:1px solid var(--wha-border); border-radius:8px;
                  font-size:.9rem; color:var(--wha-text); outline:none; transition:border-color .15s, box-shadow .15s; }
.wha-input:focus{ border-color:var(--wha-primary); box-shadow:0 0 0 3px rgba(79,70,229,.12); }

.wha-run-btn       { display:flex; align-items:center; gap:8px; background:var(--wha-primary); color:#fff;
                     border:none; border-radius:8px; padding:11px 22px; font-size:.9rem; font-weight:600; cursor:pointer;
                     transition:background .15s, transform .1s; white-space:nowrap; }
.wha-run-btn:hover { background:var(--wha-primary-h); }
.wha-run-btn:active{ transform:scale(.97); }
.wha-run-btn:disabled{ opacity:.55; cursor:not-allowed; }

/* ── Progress ───────────────────────────────────────────────────────────── */
.wha-progress-wrap  { margin-top:18px; }
.wha-progress-bar   { height:6px; background:#e2e8f0; border-radius:99px; overflow:hidden; margin-bottom:8px; }
.wha-progress-fill  { height:100%; width:0%; background:linear-gradient(90deg,#6366f1,#8b5cf6); border-radius:99px; transition:width .4s ease; }
.wha-progress-label { font-size:.8rem; color:var(--wha-muted); margin:0; }

/* ── Error ──────────────────────────────────────────────────────────────── */
.wha-error-box { display:flex; align-items:flex-start; gap:10px; background:#fef2f2; border:1px solid #fecaca;
                 border-radius:var(--radius); padding:16px 20px; color:#b91c1c; font-size:.88rem; margin-top:16px; }

/* ── Meta strip ─────────────────────────────────────────────────────────── */
.wha-meta-strip  { display:flex; gap:14px; flex-wrap:wrap; margin-bottom:20px; }
.wha-meta-card   { background:var(--wha-card); border:1px solid var(--wha-border); border-radius:var(--radius);
                   padding:18px 22px; flex:1; min-width:160px; box-shadow:var(--shadow); }
.wha-meta-value  { font-size:1.9rem; font-weight:800; line-height:1; }
.wha-meta-label  { font-size:.75rem; color:var(--wha-muted); margin-top:4px; text-transform:uppercase; letter-spacing:.05em; }
.wha-meta-sub    { font-size:.82rem; color:var(--wha-muted); margin-top:2px; }

/* ── Inner tabs ─────────────────────────────────────────────────────────── */
.wha-inner-tabs    { display:flex; gap:4px; border-bottom:2px solid var(--wha-border); margin-bottom:24px; }
.wha-inner-tab     { display:flex; align-items:center; gap:7px; padding:10px 20px; background:none; border:none;
                     border-bottom:2px solid transparent; margin-bottom:-2px; font-size:.88rem; font-weight:500;
                     color:var(--wha-muted); cursor:pointer; transition:color .15s, border-color .15s; }
.wha-inner-tab:hover { color:var(--wha-text); }
.wha-inner-tab.active{ color:var(--wha-primary); border-bottom-color:var(--wha-primary); }

/* ── Panel ──────────────────────────────────────────────────────────────── */
.wha-panel { animation: fadeIn .25s ease; }
@keyframes fadeIn { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:none} }

/* ── Observatory grade ring ─────────────────────────────────────────────── */
.obs-summary         { display:flex; align-items:center; gap:28px; background:var(--wha-card);
                       border:1px solid var(--wha-border); border-radius:var(--radius); padding:28px 32px;
                       margin-bottom:20px; box-shadow:var(--shadow); flex-wrap:wrap; }
.obs-grade-ring      { width:96px; height:96px; border-radius:50%; display:flex; align-items:center; justify-content:center;
                       font-size:2.4rem; font-weight:800; color:#fff; flex-shrink:0; box-shadow:0 4px 18px rgba(0,0,0,.18); }
.obs-summary-stats   { display:flex; gap:24px; flex-wrap:wrap; }
.obs-stat            { min-width:100px; }
.obs-stat-value      { font-size:1.5rem; font-weight:700; color:var(--wha-text); }
.obs-stat-label      { font-size:.75rem; color:var(--wha-muted); text-transform:uppercase; letter-spacing:.05em; }
.obs-stat-desc       { font-size:.82rem; color:var(--wha-muted); margin-top:2px; }

/* ── Observatory tests table ────────────────────────────────────────────── */
.wha-section-title   { font-size:1rem; font-weight:700; color:var(--wha-text); margin:0 0 14px; }
.obs-tests-grid      { display:grid; gap:10px; }
.obs-test-row        { background:var(--wha-card); border:1px solid var(--wha-border); border-radius:10px;
                       padding:14px 18px; display:grid; grid-template-columns:36px 1fr auto; gap:12px; align-items:center;
                       box-shadow:0 1px 3px rgba(0,0,0,.05); transition:box-shadow .15s; }
.obs-test-row:hover  { box-shadow:0 2px 10px rgba(0,0,0,.1); }
.obs-test-pass       { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.obs-test-pass.pass  { background:#dcfce7; color:#16a34a; }
.obs-test-pass.fail  { background:#fee2e2; color:#dc2626; }
.obs-test-pass.info  { background:#dbeafe; color:#2563eb; }
.obs-test-name       { font-size:.88rem; font-weight:600; color:var(--wha-text); }
.obs-test-desc       { font-size:.78rem; color:var(--wha-muted); margin-top:2px; }
.obs-test-modifier   { font-size:.82rem; font-weight:700; padding:3px 10px; border-radius:99px; white-space:nowrap; }
.obs-test-modifier.pos { background:#dcfce7; color:#15803d; }
.obs-test-modifier.neg { background:#fee2e2; color:#b91c1c; }
.obs-test-modifier.zero{ background:#f1f5f9; color:var(--wha-muted); }

/* ── PageSpeed device selector ──────────────────────────────────────────── */
.ps-device-tabs     { display:flex; gap:8px; margin-bottom:20px; }
.ps-device-btn      { display:flex; align-items:center; gap:7px; padding:8px 18px; border-radius:8px; border:1px solid var(--wha-border);
                      background:#fff; color:var(--wha-muted); font-size:.85rem; font-weight:500; cursor:pointer; transition:all .15s; }
.ps-device-btn.active{ background:var(--wha-primary); color:#fff; border-color:var(--wha-primary); }
.ps-device-btn:hover:not(.active){ background:#f1f5f9; }

/* ── Score gauges ───────────────────────────────────────────────────────── */
.ps-scores-row       { display:flex; gap:14px; flex-wrap:wrap; margin-bottom:20px; }
.ps-score-card       { flex:1; min-width:130px; background:var(--wha-card); border:1px solid var(--wha-border);
                       border-radius:var(--radius); padding:20px 16px; text-align:center; box-shadow:var(--shadow); }
.ps-score-ring       { width:72px; height:72px; border-radius:50%; margin:0 auto 10px; display:flex; align-items:center;
                       justify-content:center; font-size:1.4rem; font-weight:800; color:#fff; box-shadow:0 3px 12px rgba(0,0,0,.15); }
.ps-score-name       { font-size:.78rem; color:var(--wha-muted); font-weight:500; text-transform:uppercase; letter-spacing:.05em; }

/* ── CWV grid ───────────────────────────────────────────────────────────── */
.ps-cwv-grid         { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:12px; margin-bottom:20px; }
.ps-cwv-card         { background:var(--wha-card); border:1px solid var(--wha-border); border-radius:10px;
                       padding:16px; box-shadow:0 1px 3px rgba(0,0,0,.05); }
.ps-cwv-value        { font-size:1.4rem; font-weight:700; }
.ps-cwv-metric       { font-size:.78rem; color:var(--wha-muted); margin-top:4px; }
.ps-cwv-score-dot    { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:4px; vertical-align:middle; }

/* ── Opportunity / Diagnostic rows ─────────────────────────────────────── */
.ps-audit-list       { display:grid; gap:8px; margin-bottom:24px; }
.ps-audit-row        { background:var(--wha-card); border:1px solid var(--wha-border); border-radius:10px;
                       padding:13px 16px; display:flex; align-items:flex-start; gap:12px;
                       box-shadow:0 1px 3px rgba(0,0,0,.04); cursor:pointer; transition:box-shadow .15s; }
.ps-audit-row:hover  { box-shadow:0 3px 12px rgba(0,0,0,.1); }
.ps-audit-dot        { width:10px; height:10px; border-radius:50%; flex-shrink:0; margin-top:5px; }
.ps-audit-title      { font-size:.87rem; font-weight:600; color:var(--wha-text); }
.ps-audit-desc       { font-size:.78rem; color:var(--wha-muted); margin-top:3px; }
.ps-audit-savings    { margin-left:auto; font-size:.78rem; font-weight:600; color:#f59e0b; white-space:nowrap; padding:2px 8px;
                       background:#fffbeb; border-radius:99px; border:1px solid #fde68a; }

/* ── Headers ────────────────────────────────────────────────────────────── */
.wha-section-wrap    { background:var(--wha-card); border:1px solid var(--wha-border); border-radius:var(--radius);
                       padding:22px 24px; margin-bottom:16px; box-shadow:var(--shadow); }
.wha-divider         { border:none; border-top:1px solid var(--wha-border); margin:20px 0; }

/* ── Skeleton loader ────────────────────────────────────────────────────── */
.wha-skeleton        { background:linear-gradient(90deg,#f1f5f9 25%,#e2e8f0 50%,#f1f5f9 75%);
                       background-size:200% 100%; animation:shimmer 1.5s infinite; border-radius:8px; }
@keyframes shimmer   { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

/* ── Responsive ─────────────────────────────────────────────────────────── */
@media(max-width:640px){
    .wha-header        { flex-direction:column; align-items:flex-start; }
    .obs-summary       { flex-direction:column; }
    .ps-scores-row     { gap:10px; }
    .ps-cwv-grid       { grid-template-columns:1fr 1fr; }
}
</style>


{{-- ═══════════════════════════════════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════════════════════════════════ --}}
<script>
// ── Utilities ──────────────────────────────────────────────────────────────

/**
 * Return a background colour for Observatory grades (A+ → F).
 */
function gradeColour(grade) {
    if (!grade) return '#94a3b8';
    const g = grade.toUpperCase();
    if (g.startsWith('A')) return '#22c55e';
    if (g.startsWith('B')) return '#84cc16';
    if (g.startsWith('C')) return '#f59e0b';
    if (g.startsWith('D')) return '#f97316';
    return '#ef4444';
}

/**
 * Return a colour based on a 0-100 Lighthouse score.
 */
function scoreColour(score) {
    if (score === null || score === undefined) return '#94a3b8';
    if (score >= 90) return '#22c55e';
    if (score >= 50) return '#f59e0b';
    return '#ef4444';
}

/**
 * Return a dot colour based on a 0–1 audit score.
 */
function auditDotColour(score) {
    if (score === null || score === undefined) return '#94a3b8';
    if (score >= 0.9) return '#22c55e';
    if (score >= 0.5) return '#f59e0b';
    return '#ef4444';
}

// ── Tab switching ──────────────────────────────────────────────────────────

function switchInnerTab(tab) {
    ['mozilla','pagespeed'].forEach(t => {
        document.getElementById('panel-' + t).style.display  = t === tab ? '' : 'none';
        document.getElementById('tab-'   + t).classList.toggle('active', t === tab);
    });
}

let _psData = {};   // store pagespeed response globally for device switching

function switchPsDevice(device) {
    document.querySelectorAll('.ps-device-btn').forEach(b =>
        b.classList.toggle('active', b.dataset.device === device));
    renderPageSpeedDevice(device, _psData[device]);
}

// ── Main audit runner ──────────────────────────────────────────────────────

async function runHealthAudit() {
    const input = document.getElementById('wha-url-input').value.trim();
    if (!input) { alert('Please enter a URL to audit.'); return; }

    // Reset UI
    document.getElementById('wha-error').style.display   = 'none';
    document.getElementById('wha-results').style.display = 'none';

    const btn  = document.getElementById('wha-run-btn');
    const prog = document.getElementById('wha-progress-wrap');
    const fill = document.getElementById('wha-progress-fill');
    const lbl  = document.getElementById('wha-progress-label');

    btn.disabled = true;
    prog.style.display = '';
    setProgress(5, 'Connecting to APIs…');

    // Normalise: encode the domain for the URL segment
    let domain = input.replace(/^https?:\/\//i,'').replace(/\/$/,'').split('/')[0];
    let progressTimer = simulateProgress(fill, lbl);

    try {
        const response = await fetch(
            '{{ route("seo.website-health-audit", [$created_by_user_id, $client_property_id, "__DOMAIN__"]) }}'
                .replace('__DOMAIN__', encodeURIComponent(domain)),
            { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }
        );

        clearInterval(progressTimer);
        setProgress(100, 'Audit complete!');

        const json = await response.json();

        if (!response.ok || !json.success) {
            throw new Error(json.message ?? 'Audit failed.');
        }

        setTimeout(() => {
            prog.style.display = 'none';
            btn.disabled = false;
            renderResults(json);
        }, 400);

    } catch (err) {
        clearInterval(progressTimer);
        prog.style.display = 'none';
        btn.disabled = false;
        showError(err.message || 'An unexpected error occurred.');
    }
}

// ── Progress helper ────────────────────────────────────────────────────────

function setProgress(pct, msg) {
    document.getElementById('wha-progress-fill').style.width  = pct + '%';
    document.getElementById('wha-progress-label').textContent = msg;
}

function simulateProgress(fill, lbl) {
    const steps = [
        [10,'Triggering Mozilla Observatory scan…'],
        [22,'Waiting for Observatory results…'],
        [40,'Fetching PageSpeed – mobile…'],
        [58,'Fetching PageSpeed – desktop…'],
        [72,'Processing Lighthouse audits…'],
        [85,'Compiling audit report…'],
    ];
    let i = 0;
    return setInterval(() => {
        if (i < steps.length) {
            fill.style.width      = steps[i][0] + '%';
            lbl.textContent       = steps[i][1];
            i++;
        }
    }, 3500);
}

// ── Error helper ───────────────────────────────────────────────────────────

function showError(msg) {
    const el = document.getElementById('wha-error');
    document.getElementById('wha-error-msg').textContent = msg;
    el.style.display = 'flex';
}

// ── Master render ──────────────────────────────────────────────────────────

function renderResults(json) {
    const obs = json.audits?.mozilla_observatory ?? {};
    const ps  = json.audits?.pagespeed_insights  ?? {};

    // Meta strip
    renderMetaStrip(json.domain, obs, ps);

    // Panels
    renderMozillaPanel(obs);
    renderPageSpeedPanel(ps);

    document.getElementById('wha-results').style.display = '';
}

// ── Meta strip ─────────────────────────────────────────────────────────────

function renderMetaStrip(domain, obs, ps) {
    const grade     = obs.grade  ?? '—';
    const obsScore  = obs.score  ?? '—';
    const mobilePerf = ps.mobile?.category_scores?.['accessibility']?.score ?? null;
    const desktopPerf= ps.desktop?.category_scores?.['accessibility']?.score ?? null;

    // Pull performance from CWV if available (it's not in category_scores for this endpoint)
    const mLcp  = ps.mobile?.core_web_vitals?.largest_contentful_paint?.display_value ?? '—';
    const dLcp  = ps.desktop?.core_web_vitals?.largest_contentful_paint?.display_value ?? '—';

    document.getElementById('wha-meta-strip').innerHTML = `
        <div class="wha-meta-card">
            <div class="wha-meta-value" style="color:${gradeColour(grade)}">${grade}</div>
            <div class="wha-meta-label">Observatory Grade</div>
            <div class="wha-meta-sub">Score: ${obsScore} / 100</div>
        </div>
        <div class="wha-meta-card">
            <div class="wha-meta-value" style="color:#6366f1">${obs.tests_passed ?? '—'}</div>
            <div class="wha-meta-label">Tests Passed</div>
            <div class="wha-meta-sub">${obs.tests_quantity ?? 0} total tests</div>
        </div>
        <div class="wha-meta-card">
            <div class="wha-meta-value" style="color:#f59e0b">${mLcp}</div>
            <div class="wha-meta-label">Mobile LCP</div>
            <div class="wha-meta-sub">Largest Contentful Paint</div>
        </div>
        <div class="wha-meta-card">
            <div class="wha-meta-value" style="color:#3b82f6">${dLcp}</div>
            <div class="wha-meta-label">Desktop LCP</div>
            <div class="wha-meta-sub">Largest Contentful Paint</div>
        </div>
        <div class="wha-meta-card">
            <div class="wha-meta-value" style="color:#94a3b8; font-size:1rem; margin-top:4px">${domain}</div>
            <div class="wha-meta-label">Audited Domain</div>
            <div class="wha-meta-sub">${new Date().toLocaleDateString()}</div>
        </div>
    `;
}

// ── Mozilla Observatory ────────────────────────────────────────────────────

function renderMozillaPanel(obs) {
    const el = document.getElementById('mozilla-content');

    if (obs.error) {
        el.innerHTML = `<div class="wha-error-box" style="margin:0">
            <span>Observatory error: ${escHtml(obs.message ?? 'Unknown error')}</span></div>`;
        return;
    }

    const grade = obs.grade ?? '—';
    const score = obs.score ?? '—';
    const passed = obs.tests_passed ?? 0;
    const failed = obs.tests_failed ?? 0;
    const total  = obs.tests_quantity ?? 0;
    const desc   = obs.score_description ?? '';
    const likelihood = obs.likelihood_indicator ?? '';

    // Summary card
    let html = `
    <div class="obs-summary">
        <div class="obs-grade-ring" style="background:${gradeColour(grade)}">${escHtml(grade)}</div>
        <div class="obs-summary-stats">
            <div class="obs-stat">
                <div class="obs-stat-value">${score}<span style="font-size:1rem;font-weight:400;color:var(--wha-muted)">/100</span></div>
                <div class="obs-stat-label">Security Score</div>
                <div class="obs-stat-desc">${escHtml(desc)}</div>
            </div>
            <div class="obs-stat">
                <div class="obs-stat-value" style="color:#22c55e">${passed}</div>
                <div class="obs-stat-label">Tests Passed</div>
            </div>
            <div class="obs-stat">
                <div class="obs-stat-value" style="color:#ef4444">${failed}</div>
                <div class="obs-stat-label">Tests Failed</div>
            </div>
            <div class="obs-stat">
                <div class="obs-stat-value">${total}</div>
                <div class="obs-stat-label">Total Tests</div>
            </div>
            ${likelihood ? `
            <div class="obs-stat">
                <div class="obs-stat-value" style="font-size:1rem">${escHtml(likelihood)}</div>
                <div class="obs-stat-label">Risk Level</div>
            </div>` : ''}
        </div>
    </div>`;

    // Tests breakdown
    const tests = obs.tests ?? {};
    if (Object.keys(tests).length > 0) {
        html += `<div class="wha-section-wrap">
            <h3 class="wha-section-title">Security Test Results</h3>
            <div class="obs-tests-grid">`;

        // Sort: failed first, then passed
        const entries = Object.entries(tests).sort(([,a],[,b]) => {
            const ap = a.pass === true ? 1 : 0;
            const bp = b.pass === true ? 1 : 0;
            return ap - bp;
        });

        for (const [name, test] of entries) {
            const pass   = test.pass;
            const passClass = pass === true ? 'pass' : pass === false ? 'fail' : 'info';
            const passIcon  = pass === true
                ? '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>'
                : pass === false
                ? '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
                : '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';

            const mod     = test.score_modifier ?? 0;
            const modSign = mod > 0 ? '+' : '';
            const modClass= mod > 0 ? 'pos' : mod < 0 ? 'neg' : 'zero';

            html += `
            <div class="obs-test-row">
                <div class="obs-test-pass ${passClass}">${passIcon}</div>
                <div>
                    <div class="obs-test-name">${formatTestName(name)}</div>
                    ${test.description ? `<div class="obs-test-desc">${escHtml(test.description)}</div>` : ''}
                    ${test.result     ? `<div class="obs-test-desc" style="color:var(--wha-text);margin-top:4px">${escHtml(test.result)}</div>` : ''}
                </div>
                ${mod !== null ? `<div class="obs-test-modifier ${modClass}">${modSign}${mod}</div>` : ''}
            </div>`;
        }

        html += `</div></div>`;
    }

    // Response headers (collapsible)
    const headers = obs.response_headers ?? {};
    if (Object.keys(headers).length > 0) {
        html += `
        <div class="wha-section-wrap">
            <h3 class="wha-section-title">Response Headers</h3>
            <div style="display:grid;gap:6px">`;
        for (const [k, v] of Object.entries(headers)) {
            html += `<div style="display:flex;gap:8px;font-size:.82rem;border-bottom:1px solid var(--wha-border);padding-bottom:6px">
                <span style="color:var(--wha-muted);min-width:220px;font-weight:500">${escHtml(k)}</span>
                <span style="color:var(--wha-text);word-break:break-all">${escHtml(String(v))}</span>
            </div>`;
        }
        html += `</div></div>`;
    }

    el.innerHTML = html;
}

// ── PageSpeed Insights ─────────────────────────────────────────────────────

function renderPageSpeedPanel(ps) {
    _psData = ps;
    const el = document.getElementById('pagespeed-content');

    // Device switcher
    el.innerHTML = `
    <div class="ps-device-tabs">
        <button class="ps-device-btn active" data-device="mobile"
                onclick="switchPsDevice('mobile')">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
            Mobile
        </button>
        <button class="ps-device-btn" data-device="desktop"
                onclick="switchPsDevice('desktop')">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"
                 viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/>
                <line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            Desktop
        </button>
    </div>
    <div id="ps-device-content"></div>`;

    renderPageSpeedDevice('mobile', ps.mobile);
}

function renderPageSpeedDevice(device, data) {
    const el = document.getElementById('ps-device-content');

    if (!data || data.error) {
        el.innerHTML = `<div class="wha-error-box" style="margin:0">
            <span>PageSpeed error: ${escHtml(data?.message ?? 'No data available')}</span></div>`;
        return;
    }

    const cats  = data.category_scores ?? {};
    const cwv   = data.core_web_vitals ?? {};
    const opps  = data.opportunities   ?? {};
    const diags = data.diagnostics     ?? {};
    const meta  = data.meta            ?? {};

    // ── Category scores ──
    let html = `<div class="ps-scores-row">`;
    const catLabels = {
        'accessibility': 'Accessibility',
        'best-practices': 'Best Practices',
        'seo': 'SEO',
    };
    for (const [key, info] of Object.entries(cats)) {
        const sc = info.score ?? null;
        html += `<div class="ps-score-card">
            <div class="ps-score-ring" style="background:${scoreColour(sc)}">${sc ?? '—'}</div>
            <div class="ps-score-name">${catLabels[key] ?? info.title ?? key}</div>
        </div>`;
    }
    html += `</div>`;

    // ── Core Web Vitals ──
    const cwvLabels = {
        first_contentful_paint:   {label:'FCP',  name:'First Contentful Paint'},
        largest_contentful_paint: {label:'LCP',  name:'Largest Contentful Paint'},
        total_blocking_time:      {label:'TBT',  name:'Total Blocking Time'},
        cumulative_layout_shift:  {label:'CLS',  name:'Cumulative Layout Shift'},
        speed_index:              {label:'SI',   name:'Speed Index'},
        time_to_interactive:      {label:'TTI',  name:'Time to Interactive'},
        time_to_first_byte:       {label:'TTFB', name:'Time to First Byte'},
        interaction_to_next_paint:{label:'INP',  name:'Interaction to Next Paint'},
    };

    html += `<div class="wha-section-wrap">
        <h3 class="wha-section-title">Core Web Vitals — ${device.charAt(0).toUpperCase()+device.slice(1)}</h3>
        <div class="ps-cwv-grid">`;

    for (const [key, info] of Object.entries(cwvLabels)) {
        const m = cwv[key] ?? {};
        const sc = m.score ?? null;
        const val = m.display_value ?? '—';
        html += `<div class="ps-cwv-card">
            <div class="ps-cwv-value" style="color:${auditDotColour(sc)}">
                <span class="ps-cwv-score-dot" style="background:${auditDotColour(sc)}"></span>${escHtml(val)}
            </div>
            <div class="ps-cwv-metric"><strong>${info.label}</strong> — ${info.name}</div>
        </div>`;
    }
    html += `</div></div>`;

    // ── Opportunities ──
    const oppEntries = Object.entries(opps);
    if (oppEntries.length > 0) {
        html += `<div class="wha-section-wrap">
            <h3 class="wha-section-title">Opportunities <span style="font-size:.78rem;color:var(--wha-muted);font-weight:400">(potential savings)</span></h3>
            <div class="ps-audit-list">`;

        for (const [, o] of oppEntries.sort(([,a],[,b]) => (b.savings_ms??0)-(a.savings_ms??0))) {
            const sc  = o.score ?? null;
            const sav = o.savings_ms ? Math.round(o.savings_ms) + ' ms' : null;
            html += `<div class="ps-audit-row">
                <div class="ps-audit-dot" style="background:${auditDotColour(sc)}"></div>
                <div style="flex:1">
                    <div class="ps-audit-title">${escHtml(o.title ?? '')}</div>
                    ${o.description ? `<div class="ps-audit-desc">${escHtml(stripLinks(o.description))}</div>` : ''}
                </div>
                ${sav ? `<div class="ps-audit-savings">Save ~${sav}</div>` : ''}
            </div>`;
        }
        html += `</div></div>`;
    }

    // ── Diagnostics ──
    const diagEntries = Object.entries(diags).filter(([,d]) => d.score !== null && d.score < 1);
    if (diagEntries.length > 0) {
        html += `<div class="wha-section-wrap">
            <h3 class="wha-section-title">Diagnostics <span style="font-size:.78rem;color:var(--wha-muted);font-weight:400">(items needing attention)</span></h3>
            <div class="ps-audit-list">`;

        for (const [, d] of diagEntries.sort(([,a],[,b]) => (a.score??1)-(b.score??1))) {
            html += `<div class="ps-audit-row">
                <div class="ps-audit-dot" style="background:${auditDotColour(d.score)}"></div>
                <div>
                    <div class="ps-audit-title">${escHtml(d.title ?? '')}</div>
                    ${d.description ? `<div class="ps-audit-desc">${escHtml(stripLinks(d.description))}</div>` : ''}
                    ${d.display_value ? `<div class="ps-audit-desc" style="color:var(--wha-text);margin-top:2px">${escHtml(d.display_value)}</div>` : ''}
                </div>
            </div>`;
        }
        html += `</div></div>`;
    }

    // ── Lighthouse meta ──
    if (meta.fetch_time || meta.lighthouse_version) {
        html += `<div style="font-size:.78rem;color:var(--wha-muted);text-align:right;margin-top:8px">
            ${meta.lighthouse_version ? 'Lighthouse v'+escHtml(meta.lighthouse_version)+' · ' : ''}
            ${meta.fetch_time ? 'Scanned ' + new Date(meta.fetch_time).toLocaleString() : ''}
        </div>`;
    }

    el.innerHTML = html;
}

// ── String helpers ─────────────────────────────────────────────────────────

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;');
}

/** Strip markdown-style [text](url) links → plain text */
function stripLinks(str) {
    return (str ?? '').replace(/\[([^\]]+)\]\([^)]+\)/g, '$1');
}

/** Convert snake_case / kebab-case keys to Title Case for display */
function formatTestName(key) {
    return key
        .replace(/[-_]/g, ' ')
        .replace(/\b\w/g, c => c.toUpperCase());
}
</script>

@endsection