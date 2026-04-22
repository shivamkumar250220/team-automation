@extends('layouts.app')

@section('title', 'Core Web Vitals Report')
@section('page_header', 'Core Web Vitals Report')
@section('page_icon', 'mdi mdi-speedometer')

@section('breadcrumb')
    <li class="breadcrumb-item active">SEO</li>
    <li class="breadcrumb-item active" aria-current="page">Core Web Vitals Report</li>
@endsection

@push('styles')
<style>
/* ── Score ring ─────────────────────────────────────────────────── */
.score-ring-wrap { position: relative; width: 120px; height: 120px; }
.score-ring-wrap svg { transform: rotate(-90deg); }
.score-ring-wrap .score-label {
    position: absolute; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    font-size: 1.6rem; font-weight: 700; line-height: 1;
}
.score-ring-wrap .score-label small { font-size: 0.65rem; font-weight: 500; color: #6b7280; margin-top: 2px; }

/* ── Metric card ────────────────────────────────────────────────── */
.vital-card {
    border-radius: 12px; padding: 18px 20px;
    border: 1px solid #e5e7eb; background: #fff;
    transition: box-shadow .2s;
}
.vital-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.vital-card .metric-value { font-size: 1.5rem; font-weight: 700; }
.vital-card .metric-label { font-size: .78rem; color: #6b7280; margin-top: 2px; }
.vital-card .metric-badge {
    display: inline-block; padding: 2px 10px;
    border-radius: 20px; font-size: .7rem; font-weight: 600; margin-top: 6px;
}

/* Thresholds colours */
.score-good    { color: #15803d; }
.score-average { color: #b45309; }
.score-poor    { color: #b91c1c; }
.bg-good       { background: #dcfce7; color: #15803d; }
.bg-average    { background: #fef9c3; color: #b45309; }
.bg-poor       { background: #fee2e2; color: #b91c1c; }
.ring-good     { stroke: #22c55e; }
.ring-average  { stroke: #eab308; }
.ring-poor     { stroke: #ef4444; }

/* ── Strategy tabs ──────────────────────────────────────────────── */
.strategy-tab-btn {
    border: 1px solid #d1d5db; background: #f9fafb;
    border-radius: 8px; padding: 6px 22px;
    font-weight: 600; cursor: pointer; transition: all .15s;
}
.strategy-tab-btn.active { background: #1e40af; color: #fff; border-color: #1e40af; }

/* ── Thresholds legend ──────────────────────────────────────────── */
.legend-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 4px; }

/* ── Saved section select ───────────────────────────────────────── */
#savedDateSelect { max-width: 420px; }
.saved-meta-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: #f1f5f9; border-radius: 8px;
    padding: 6px 14px; font-size: .78rem; color: #475569;
}
</style>
@endpush

@section('content')
<div class="row g-4">
    
    @if(!$savedResults->isEmpty())
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                {{-- Section header --}}
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="card-title mb-0">
                            Saved Core Web Vital Results
                        </h5>
                        <p class="text-muted small mt-1 mb-0">
                            Select a previously analysed snapshot to review its metrics.
                            @if (! Auth::user()->role->name == 'admin' && ! Auth::user()->role->name == 'manager')
                                <span class="badge bg-primary bg-opacity-10 text-primary ms-1">Showing all users</span>
                            @endif
                        </p>
                    </div>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary">
                        {{ $savedResults->count() }} {{ Str::plural('record', $savedResults->count()) }}
                    </span>
                </div>
                    {{-- Date / record selector --}}
                    <div class="row g-3 align-items-end mb-4">
                        <div class="col-md-6 col-lg-5">
                            <label class="form-label fw-semibold" for="savedDateSelect">Select Report</label>
                            <select class="form-select" id="savedDateSelect">
                                <option value="">— Choose a saved snapshot —</option>
                                @foreach($savedResults as $record)
                                    <option value="{{ $record->id }}"
                                        data-record="{{ json_encode($record->toArray()) }}">
                                        {{ \Carbon\Carbon::parse($record->created_at)->format('d M Y, h:i A') }}
                                        — {{ $record->url }}
                                        @if (! Auth::user()->role->name == 'admin' && ! Auth::user()->role->name == 'manager')
                                            (User #{{ $record->user_id }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Saved result display (hidden until a record is chosen) --}}
                    <div id="savedResultDisplay" style="display:none;">

                        {{-- Meta bar --}}
                        <div class="d-flex flex-wrap gap-2 mb-4" id="savedMetaBar"></div>

                        {{-- Strategy tabs (separate from the live-results tabs) --}}
                        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold mb-0">Core Web Vitals Assessment</h6>
                                <p class="text-muted small mb-0">Powered by Google PageSpeed Insights</p>
                            </div>
                            <div class="d-flex gap-2" id="savedStrategyTabs">
                                <button class="strategy-tab-btn active" data-saved-strategy="mobile">
                                    <i class="mdi mdi-cellphone me-1"></i> Mobile
                                </button>
                                <button class="strategy-tab-btn" data-saved-strategy="desktop">
                                    <i class="mdi mdi-monitor me-1"></i> Desktop
                                </button>
                            </div>
                        </div>

                        <div id="savedVitalsContent"></div>

                        <div class="d-flex gap-4 mt-4 flex-wrap">
                            <span><span class="legend-dot" style="background:#22c55e;"></span> Good</span>
                            <span><span class="legend-dot" style="background:#eab308;"></span> Needs Improvement</span>
                            <span><span class="legend-dot" style="background:#ef4444;"></span> Poor</span>
                        </div>
                    </div>

            </div>
        </div>
    </div>
    @endif
    {{-- ── Form Card ──────────────────────────────────────────────────────── --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Core Web Vitals Report</h5>
                <p class="card-subtitle mb-3">Analyze the core web vitals for your website using Google PageSpeed Insights</p>

                <form id="corewebvitalsform" novalidate>
                    @csrf
                    {{-- Hidden context fields --}}
                    <input type="hidden" name="domainmanagement_id" value="{{ $domainmanagement_id }}">
                    <input type="hidden" name="client_property_id"  value="{{ $client_property_id }}">

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Our Client <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="ourclientInput" name="ourclient"
                                placeholder="https://example.com" value="{{ $domain }}" required>
                            <div class="invalid-feedback">Please enter a valid URL (include https://).</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary" id="generateBtn">
                            <span id="generateBtnText">Generate Report</span>
                            <span id="generateBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                        </button>
                        <button type="reset" class="btn btn-light" id="resetBtn">Reset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Error ──────────────────────────────────────────────────────────── --}}
    <div class="col-12" id="errorSection" style="display:none;">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-2"></i>
            <span id="errorMessage"></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>

    {{-- ── Live Results ─────────────────────────────────────────────────────── --}}
    <div class="col-12" id="resultsSection" style="display:none;">
        <div class="card">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
                    <div>
                        <h5 class="card-title mb-0">Results for <span id="resultUrl" class="text-primary"></span></h5>
                        <p class="text-muted small mt-1">Powered by Google PageSpeed Insights</p>
                    </div>
                    <div class="d-flex gap-2" id="strategyTabs">
                        <button class="strategy-tab-btn active" data-strategy="mobile">
                            <i class="mdi mdi-cellphone me-1"></i> Mobile
                        </button>
                        <button class="strategy-tab-btn" data-strategy="desktop">
                            <i class="mdi mdi-monitor me-1"></i> Desktop
                        </button>
                    </div>
                </div>

                <div id="vitalsContent"></div>

                <div class="d-flex gap-4 mt-4 flex-wrap">
                    <span><span class="legend-dot" style="background:#22c55e;"></span> Good</span>
                    <span><span class="legend-dot" style="background:#eab308;"></span> Needs Improvement</span>
                    <span><span class="legend-dot" style="background:#ef4444;"></span> Poor</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
/* ══════════════════════════════════════════════════════════════════
   Core Web Vitals — client-side logic
   ══════════════════════════════════════════════════════════════════ */

let allData = {};
let activeStrategy = 'mobile';

let savedActiveStrategy = 'mobile';

// ── Metric thresholds ─────────────────────────────────────────────
const METRICS = [
    {
        key: 'lcp', label: 'Largest Contentful Paint', abbr: 'LCP',
        good: 2500, poor: 4000, unit: 'ms', cwv: true,
        desc: 'Measures loading performance. Should occur within 2.5 s.'
    },
    {
        key: 'cls', label: 'Cumulative Layout Shift', abbr: 'CLS',
        good: 0.1, poor: 0.25, unit: '', cwv: true,
        desc: 'Measures visual stability. Should be less than 0.1.'
    },
    {
        key: 'tbt', label: 'Total Blocking Time', abbr: 'TBT',
        good: 200, poor: 600, unit: 'ms', cwv: false,
        desc: 'Proxy for FID/INP. Should be less than 200 ms.'
    },
    {
        key: 'inp', label: 'Interaction to Next Paint', abbr: 'INP',
        good: 200, poor: 500, unit: 'ms', cwv: true,
        desc: 'Measures interactivity. Should be less than 200 ms.'
    },
    {
        key: 'fcp', label: 'First Contentful Paint', abbr: 'FCP',
        good: 1800, poor: 3000, unit: 'ms', cwv: false,
        desc: 'Time until first content appears. Should be < 1.8 s.'
    },
    {
        key: 'ttfb', label: 'Time to First Byte', abbr: 'TTFB',
        good: 800, poor: 1800, unit: 'ms', cwv: false,
        desc: 'Server response time. Should be < 800 ms.'
    },
    {
        key: 'si', label: 'Speed Index', abbr: 'SI',
        good: 3400, poor: 5800, unit: 'ms', cwv: false,
        desc: 'How quickly content is visually populated. Should be < 3.4 s.'
    },
    {
        key: 'tti', label: 'Time to Interactive', abbr: 'TTI',
        good: 3800, poor: 7300, unit: 'ms', cwv: false,
        desc: 'When the page is fully interactive. Should be < 3.8 s.'
    },
];

// ── Helpers ───────────────────────────────────────────────────────
function scoreClass(score) {
    if (score === null || score === undefined) return '';
    if (score >= 0.9) return 'good';
    if (score >= 0.5) return 'average';
    return 'poor';
}

function thresholdClass(value, good, poor) {
    if (value === null || value === undefined) return 'average';
    if (value <= good) return 'good';
    if (value <= poor) return 'average';
    return 'poor';
}

function badgeLabel(cls) {
    return { good: 'Good', average: 'Needs Improvement', poor: 'Poor' }[cls] ?? '—';
}

function buildScoreRing(score) {
    const r = 52, cx = 60, cy = 60;
    const circ = 2 * Math.PI * r;
    const dash  = (score / 100) * circ;
    const cls   = score >= 90 ? 'good' : score >= 50 ? 'average' : 'poor';
    const color = { good: '#22c55e', average: '#eab308', poor: '#ef4444' }[cls];
    return `
    <div class="score-ring-wrap mx-auto">
        <svg width="120" height="120" viewBox="0 0 120 120">
            <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="#e5e7eb" stroke-width="10"/>
            <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${color}" stroke-width="10"
                stroke-dasharray="${dash} ${circ}" stroke-linecap="round"/>
        </svg>
        <div class="score-label score-${cls}">
            ${score}
            <small>Score</small>
        </div>
    </div>`;
}

// ── Build vitals HTML (shared by live + saved renders) ────────────
function buildVitalsHtml(d, strategy) {
    let html = `
    <div class="row g-3 mb-4 align-items-center">
        <div class="col-auto text-center">
            ${buildScoreRing(d.performance_score)}
            <p class="mt-2 mb-0 fw-semibold small">Performance</p>
        </div>
        <div class="col">
            <h6 class="fw-bold mb-1">Core Web Vitals Assessment</h6>
            <p class="text-muted small mb-0">
                ${strategy === 'mobile' ? '<i class="mdi mdi-cellphone"></i> Mobile' : '<i class="mdi mdi-monitor"></i> Desktop'}
                analysis — Google uses mobile scores for ranking signals.
            </p>
        </div>
    </div>
    <div class="row g-3">`;

    METRICS.forEach(m => {
        const metric = d[m.key];
        const display = metric?.display ?? '—';
        const val     = metric?.value   ?? null;
        const score   = metric?.score   ?? null;

        const cls = score !== null
            ? scoreClass(score)
            : thresholdClass(val, m.good, m.poor);

        const cwvTag = m.cwv
            ? `<span class="badge bg-primary bg-opacity-10 text-primary ms-1" style="font-size:.65rem;">Core</span>`
            : '';

        html += `
        <div class="col-md-3 col-sm-6">
            <div class="vital-card h-100">
                <div class="d-flex align-items-center gap-1 mb-1">
                    <span class="fw-semibold small">${m.abbr}</span>${cwvTag}
                </div>
                <div class="metric-value score-${cls}">${display}</div>
                <div class="metric-label">${m.label}</div>
                <span class="metric-badge bg-${cls}">${badgeLabel(cls)}</span>
                <p class="text-muted mt-2 mb-0" style="font-size:.72rem;">${m.desc}</p>
            </div>
        </div>`;
    });

    html += `</div>`;
    return html;
}

// ── Live render ───────────────────────────────────────────────────
function render(strategy) {
    const d = allData[strategy];
    if (!d) return;
    document.getElementById('vitalsContent').innerHTML = buildVitalsHtml(d, strategy);
}

// ── Strategy tab switch (live) ────────────────────────────────────
document.getElementById('strategyTabs').addEventListener('click', function (e) {
    const btn = e.target.closest('.strategy-tab-btn');
    if (!btn) return;
    document.querySelectorAll('#strategyTabs .strategy-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activeStrategy = btn.dataset.strategy;
    render(activeStrategy);
});

// ── Form submit ───────────────────────────────────────────────────
document.getElementById('corewebvitalsform').addEventListener('submit', async function (e) {
    e.preventDefault();

    const input = document.getElementById('ourclientInput');
    if (!input.value.trim()) {
        input.classList.add('is-invalid');
        return;
    }
    input.classList.remove('is-invalid');

    setLoading(true);
    document.getElementById('errorSection').style.display   = 'none';
    document.getElementById('resultsSection').style.display = 'none';

    try {
        const formData = new FormData(this);
        const resp = await fetch("{{ route('core.web.vitals.form', [$domainmanagement_id, $client_property_id]) }}", {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: formData,
        });

        const json = await resp.json();

        if (!resp.ok || json.error) {
            showError(json.error ?? 'An unexpected error occurred. Please try again.');
            return;
        }

        allData = json.data;
        document.getElementById('resultUrl').textContent = json.url;
        activeStrategy = 'mobile';
        document.querySelectorAll('#strategyTabs .strategy-tab-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.strategy === 'mobile');
        });
        render('mobile');

        document.getElementById('resultsSection').style.display = 'block';
        document.getElementById('resultsSection').scrollIntoView({ behavior: 'smooth' });

    } catch (err) {
        showError('Network error: ' + err.message);
    } finally {
        setLoading(false);
    }
});

// ── Reset ─────────────────────────────────────────────────────────
document.getElementById('resetBtn').addEventListener('click', function () {
    document.getElementById('resultsSection').style.display = 'none';
    document.getElementById('errorSection').style.display   = 'none';
    document.getElementById('vitalsContent').innerHTML = '';
    allData = {};
});

// ── Helpers ───────────────────────────────────────────────────────
function setLoading(on) {
    document.getElementById('generateBtn').disabled = on;
    document.getElementById('generateBtnSpinner').classList.toggle('d-none', !on);
    document.getElementById('generateBtnText').textContent = on ? 'Analysing…' : 'Generate Report';
}

function showError(msg) {
    document.getElementById('errorMessage').textContent = msg;
    document.getElementById('errorSection').style.display = 'block';
}

// ══════════════════════════════════════════════════════════════════
//  SAVED RESULTS — logic
// ══════════════════════════════════════════════════════════════════

/**
 * Convert a flat DB row (mobile_lcp_display, etc.) back into the
 * nested format that buildVitalsHtml() expects.
 */
function flatRowToStrategyData(row, strategy) {
    const METRIC_KEYS = ['lcp', 'cls', 'tbt', 'inp', 'fcp', 'ttfb', 'si', 'tti'];
    const d = { performance_score: row[`${strategy}_performance_score`] ?? 0 };
    METRIC_KEYS.forEach(m => {
        d[m] = {
            display : row[`${strategy}_${m}_display`] ?? '—',
            value   : row[`${strategy}_${m}_value`]   ?? null,
            score   : row[`${strategy}_${m}_score`]   ?? null,
        };
    });
    return d;
}

// ── Render a saved record ─────────────────────────────────────────
function renderSaved(row, strategy) {
    const d = flatRowToStrategyData(row, strategy);
    document.getElementById('savedVitalsContent').innerHTML = buildVitalsHtml(d, strategy);
}

// ── Date select change ────────────────────────────────────────────
const savedDateSelect = document.getElementById('savedDateSelect');
if (savedDateSelect) {
    savedDateSelect.addEventListener('change', function () {
        const display = document.getElementById('savedResultDisplay');

        if (!this.value) {
            display.style.display = 'none';
            return;
        }

        const selectedOption = this.options[this.selectedIndex];
        const row = JSON.parse(selectedOption.dataset.record);

        // Meta bar
        document.getElementById('savedMetaBar').innerHTML = `
            <span class="saved-meta-badge">
                <i class="mdi mdi-link-variant"></i> ${row.url}
            </span>
            <span class="saved-meta-badge">
                <i class="mdi mdi-calendar-outline"></i>
                ${new Date(row.created_at).toLocaleString('en-IN', {
                    day: '2-digit', month: 'short', year: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                })}
            </span>
            @if (! Auth::user()->role->name == 'admin' && ! Auth::user()->role->name == 'manager')
            <span class="saved-meta-badge">
                <i class="mdi mdi-account-outline"></i> User #${row.user_id}
            </span>
            @endif
        `;

        // Reset to mobile tab
        savedActiveStrategy = 'mobile';
        document.querySelectorAll('#savedStrategyTabs .strategy-tab-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.savedStrategy === 'mobile');
        });

        renderSaved(row, savedActiveStrategy);
        display.style.display = 'block';
        display.scrollIntoView({ behavior: 'smooth' });
    });
}

// ── Saved strategy tab switch ─────────────────────────────────────
const savedStrategyTabs = document.getElementById('savedStrategyTabs');
if (savedStrategyTabs) {
    savedStrategyTabs.addEventListener('click', function (e) {
        const btn = e.target.closest('.strategy-tab-btn');
        if (!btn) return;

        document.querySelectorAll('#savedStrategyTabs .strategy-tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        savedActiveStrategy = btn.dataset.savedStrategy;

        const selectedOption = savedDateSelect.options[savedDateSelect.selectedIndex];
        if (!selectedOption?.dataset?.record) return;
        const row = JSON.parse(selectedOption.dataset.record);
        renderSaved(row, savedActiveStrategy);
    });
}
</script>
@endpush