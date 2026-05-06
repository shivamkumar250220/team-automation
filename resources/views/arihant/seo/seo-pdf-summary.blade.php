@extends('layouts.app')

@section('title', 'SEO PDF Summary — ' . ($group['display_url'] ?? ''))
@section('page_header', 'SEO PDF Summary')
@section('page_icon', 'mdi mdi-magnify')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('seo.dashboard', [$created_by_user_id, $client_property_id]) }}">SEO Tools</a>
    </li>
    <li class="breadcrumb-item active" aria-current="page">Summary</li>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<style>
/* ══════════════════════════════════════════════════════════════════
   SEO PDF SUMMARY — Page Layout
   ══════════════════════════════════════════════════════════════════ */
.seo-wrap {
    background: #f6f8fa;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
}

/* ── Top nav bar ─── */
.seo-topbar {
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    padding: 0 28px;
    display: flex;
    align-items: center;
    gap: 0;
    height: 52px;
}
.seo-topbar .brand {
    display: flex; align-items: center; gap: 8px;
    font-weight: 700; font-size: 1.05rem; color: #111827;
    margin-right: 32px;
}
.seo-topbar .brand .brand-dot {
    width: 22px; height: 22px; border-radius: 50%;
    background: #16a34a; display: flex; align-items: center; justify-content: center;
}
.seo-topbar .brand .brand-dot i { color: #fff; font-size: 0.75rem; }

/* ── Summary pane ─── */
.summary-pane { padding: 28px; background: #f6f8fa; min-height: 500px; }

/* ── Summary header card ─── */
.summary-header-card {
    background: #fff; border: 1px solid #e5e7eb;
    border-radius: 12px; padding: 24px 28px; margin-bottom: 28px;
}
.summary-domain-title { font-size: 1.4rem; font-weight: 800; color: #111827; }
.summary-meta { font-size: .8rem; color: #9ca3af; margin-top: 4px; }

/* ── Section heading (Ranking / CWV / Reporting) ─── */
.section-block { margin-bottom: 36px; }
.section-main-heading {
    display: flex; align-items: center; gap: 10px;
    font-size: 1.1rem; font-weight: 800; color: #111827;
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e5e7eb;
}
.section-main-heading i { font-size: 1.2rem; }

/* ── Date heading within a section ─── */
.date-block { margin-bottom: 24px; }
.date-heading {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: .95rem; font-weight: 700; color: #374151;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 6px 16px;
    margin-bottom: 16px;
}
.date-heading i { font-size: .9rem; color: #6b7280; }

/* ── Stats grid ─── */
.summary-stats-grid {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 1px; background: #e5e7eb;
    border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;
    margin-bottom: 20px;
}
.stat-box {
    background: #fff; padding: 20px 24px;
    display: flex; flex-direction: column; align-items: flex-start;
}
.stat-box .stat-label  { font-size: .7rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; }
.stat-box .stat-value  { font-size: 2.2rem; font-weight: 800; color: #111827; line-height: 1; }
.stat-box .stat-sub    { font-size: .8rem; color: #9ca3af; margin-top: 4px; }
.stat-box .stat-value.green { color: #16a34a; }
.stat-box .stat-value.red   { color: #dc2626; }

/* ── Score ring ─── */
.score-ring-wrap { position: relative; width: 100px; height: 100px; }
.score-ring-wrap svg { transform: rotate(-90deg); }
.score-ring-wrap .score-label {
    position: absolute; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    font-size: 1.4rem; font-weight: 700; line-height: 1;
}
.score-ring-wrap .score-label small { font-size: .6rem; font-weight: 500; color: #6b7280; margin-top: 2px; }
.score-good    { color: #15803d; }
.score-average { color: #b45309; }
.score-poor    { color: #b91c1c; }

/* ── Issues card ─── */
.issues-card {
    background: #fff; border: 1px solid #e5e7eb;
    border-radius: 12px; overflow: hidden; margin-bottom: 16px;
}
.issues-card-header {
    padding: 16px 24px; border-bottom: 1px solid #f1f5f9;
    font-weight: 700; font-size: .95rem; color: #111827;
}
.issues-card-header small { display: block; font-weight: 400; font-size: .78rem; color: #9ca3af; margin-top: 2px; }

/* ── Plain table (replaces DataTable) ─── */
.plain-table { width: 100%; border-collapse: collapse; }
.plain-table th { padding: 10px 16px; text-align: left; font-size: .78rem; font-weight: 600; color: #6b7280; background: #f9fafb; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
.plain-table td { padding: 11px 16px; font-size: .84rem; border-bottom: 1px solid #f1f5f9; color: #374151; vertical-align: middle; }
.plain-table tr:last-child td { border-bottom: none; }
.plain-table tr:hover td { background: #fafafa; }

/* ── Issues table ─── */
.issues-table { width: 100%; border-collapse: collapse; }
.issues-table th { padding: 10px 20px; text-align: left; font-size: .78rem; font-weight: 600; color: #6b7280; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
.issues-table td { padding: 12px 20px; font-size: .875rem; border-bottom: 1px solid #f1f5f9; color: #374151; }
.issues-table tr:last-child td { border-bottom: none; }
.issues-table tr:hover td { background: #f9fafb; }
.sev-high     { color: #b45309; font-weight: 600; }
.sev-medium   { color: #9ca3af; font-weight: 600; }
.sev-critical { color: #b91c1c; font-weight: 600; }
.sev-low      { color: #6b7280; font-weight: 600; }

/* ── CWV vital cards ─── */
.vital-card {
    border-radius: 12px; padding: 16px 18px;
    border: 1px solid #e5e7eb; background: #fff;
    transition: box-shadow .2s;
}
.vital-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
.vital-card .metric-value { font-size: 1.4rem; font-weight: 700; }
.vital-card .metric-label { font-size: .75rem; color: #6b7280; margin-top: 2px; }
.vital-card .metric-badge {
    display: inline-block; padding: 2px 10px;
    border-radius: 20px; font-size: .68rem; font-weight: 600; margin-top: 6px;
}
.bg-good    { background: #dcfce7; color: #15803d; }
.bg-average { background: #fef9c3; color: #b45309; }
.bg-poor    { background: #fee2e2; color: #b91c1c; }
.legend-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 4px; }

.strategy-tab-btn {
    border: 1px solid #d1d5db; background: #f9fafb;
    border-radius: 8px; padding: 6px 20px;
    font-weight: 600; cursor: pointer; transition: all .15s; font-size: .85rem;
}
.strategy-tab-btn.active { background: #1e40af; color: #fff; border-color: #1e40af; }

/* ── Ranking table helpers ─── */
.keyword-section-title {
    font-size: .9rem; font-weight: 700; color: #374151;
    margin: 18px 0 8px;
    display: flex; align-items: center; gap: 6px;
}
.keyword-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: .75rem; font-weight: 600; margin-right: 4px; }
.position-badge { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; font-weight: 700; font-size: .78rem; }
.pos-top3  { background: #d1fae5; color: #065f46; }
.pos-top10 { background: #dbeafe; color: #1e40af; }
.pos-other { background: #f3f4f6; color: #6b7280; }
.result-link { max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; }
.competitor-tag { background: #fef3c7; color: #92400e; }

/* ── Empty state ─── */
.empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
.empty-state i { font-size: 3rem; margin-bottom: 12px; display: block; }

/* ── Divider between date blocks ─── */
.date-block + .date-block { border-top: 1px dashed #e5e7eb; padding-top: 20px; }

@media (max-width: 640px) {
    .summary-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .summary-pane       { padding: 20px 16px; }
    .seo-topbar         { padding: 0 16px; flex-wrap: wrap; gap: 8px; height: auto; min-height: 52px; }
}

@keyframes mdi-spin {
    0%   { transform: rotate(0deg);   }
    100% { transform: rotate(360deg); }
}
.mdi-spin::before { display: inline-block; animation: mdi-spin .75s steps(8) infinite; }
</style>
@endpush

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="seo-wrap card p-0 overflow-hidden">

            {{-- ── Top nav bar ── --}}
            <div class="seo-topbar" style="justify-content: space-between;">
                <div class="brand">
                    <div class="brand-dot"><i class="mdi mdi-check"></i></div>
                    SEO PDF Summary - {{ $group['display_url'] ?? '—' }}
                </div>
                <button id="downloadPdfBtn" class="btn btn-sm btn-dark d-flex align-items-center gap-2" style="font-size:.8rem;padding:6px 16px;border-radius:8px;font-weight:600;">
                    <i class="mdi mdi-file-pdf-box" style="font-size:1.1rem;"></i>
                    Download whole PDF of the analysis
                </button>
            </div>

            {{-- ── Summary pane ── --}}
            <div class="summary-pane">

                {{-- ── Domain header ── --}}
                <div class="summary-header-card mb-4">
                    <div class="text-muted mb-1"
                        style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">
                        DOMAIN SUMMARY
                    </div>
                    <div class="summary-domain-title" id="us_domain">—</div>
                    <div class="summary-meta" id="us_meta">—</div>
                </div>

                {{-- ── Empty state (no data at all) ── --}}
                <div id="us_emptyState" style="display:none;" class="empty-state">
                    <i class="mdi mdi-chart-line-variant"></i>
                    <p class="mb-0 fw-semibold">No data found for this URL</p>
                    <p class="small mt-1">Run a new analysis from the SEO Tools dashboard.</p>
                    <a href="{{ route('seo.dashboard', [$created_by_user_id, $client_property_id]) }}"
                    class="btn btn-sm btn-primary mt-2">
                        Go to SEO Tools
                    </a>
                </div>

                {{-- ══ RANKING SECTION ══ --}}
                <div id="us_section_ranking" class="section-block" style="display:none;">
                    <div class="section-main-heading">
                        <i class="mdi mdi-chart-bar text-primary"></i>
                        Ranking &amp; Competitor Reports
                    </div>

                    {{-- Stats grid (updated per first session by default) --}}
                    <div class="summary-stats-grid mb-4"
                        style="border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;background:#e5e7eb;gap:1px;">
                        <div class="stat-box">
                            <div class="stat-label">Keywords Tracked</div>
                            <div class="stat-value" id="us_kwCount">—</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Location</div>
                            <div class="stat-value" id="us_location" style="font-size:1.2rem;">—</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Best Position</div>
                            <div class="stat-value green" id="us_bestPos">—</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-label">Keywords in Top 10</div>
                            <div class="stat-value" id="us_top10">—</div>
                        </div>
                    </div>

                    {{-- Date blocks rendered by JS --}}
                    <div id="us_rankingContent"></div>
                </div>

                {{-- ══ CORE WEB VITALS SECTION ══ --}}
                <div id="us_section_cwv" class="section-block" style="display:none;">
                    <div class="section-main-heading">
                        <i class="mdi mdi-speedometer text-success"></i>
                        Core Web Vitals
                    </div>
                    {{-- Date blocks rendered by JS --}}
                    <div id="us_cwvContent"></div>
                </div>

                {{-- ══ REPORTING SECTION ══ --}}
                <div id="us_section_reporting" class="section-block">
                    <div class="section-main-heading">
                        <i class="mdi mdi-table-large text-warning"></i>
                        Reporting
                    </div>
                    <div class="issues-card">
                        <div class="issues-card-header">
                            Reporting Sheet
                            <small>Saved reporting sessions for this domain</small>
                        </div>
                        <div id="us_reportingContent" class="p-3">
                            <div class="empty-state py-4">
                                <i class="mdi mdi-table-large"></i>
                                <p class="mb-0 fw-semibold">No reporting data saved for this URL yet</p>
                                <p class="small mt-1">Generate a reporting sheet from the New Analysis tab.</p>
                                <a href="{{ route('seo.dashboard', [$created_by_user_id, $client_property_id]) }}"
                                class="btn btn-sm btn-primary mt-2">
                                    Generate Reporting Sheet
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Snippet modal (ranking results) --}}
                <div class="modal fade" id="snippetModal" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h6 class="modal-title" id="snippetModalTitle"></h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted mb-2" id="snippetModalUrl"></p>
                                <p id="snippetModalBody" class="mb-0"></p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- /summary-pane --}}

        </div>{{-- /seo-wrap --}}
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
/* ══════════════════════════════════════════════════════════════════
   PAGE DATA  (injected from PHP)
   ══════════════════════════════════════════════════════════════════ */
const GROUP_DATA         = @json($group);
const CREATED_BY_USER_ID = {{ $created_by_user_id }};
const CLIENT_PROPERTY_ID = {{ $client_property_id }};

/* ══════════════════════════════════════════════════════════════════
   CWV METRIC DEFINITIONS
   ══════════════════════════════════════════════════════════════════ */
const CWV_METRICS = [
    { key:'lcp',  label:'Largest Contentful Paint',  abbr:'LCP',  good:2500, poor:4000, unit:'ms', cwv:true,  desc:'Measures loading performance. Should be < 2.5s.' },
    { key:'cls',  label:'Cumulative Layout Shift',    abbr:'CLS',  good:0.1,  poor:0.25, unit:'',   cwv:true,  desc:'Measures visual stability. Should be < 0.1.' },
    { key:'tbt',  label:'Total Blocking Time',        abbr:'TBT',  good:200,  poor:600,  unit:'ms', cwv:false, desc:'Proxy for FID/INP. Should be < 200ms.' },
    { key:'inp',  label:'Interaction to Next Paint',  abbr:'INP',  good:200,  poor:500,  unit:'ms', cwv:true,  desc:'Measures interactivity. Should be < 200ms.' },
    { key:'fcp',  label:'First Contentful Paint',     abbr:'FCP',  good:1800, poor:3000, unit:'ms', cwv:false, desc:'Time until first content appears. < 1.8s.' },
    { key:'ttfb', label:'Time to First Byte',         abbr:'TTFB', good:800,  poor:1800, unit:'ms', cwv:false, desc:'Server response time. < 800ms.' },
    { key:'si',   label:'Speed Index',                abbr:'SI',   good:3400, poor:5800, unit:'ms', cwv:false, desc:'Visual content population speed. < 3.4s.' },
    { key:'tti',  label:'Time to Interactive',        abbr:'TTI',  good:3800, poor:7300, unit:'ms', cwv:false, desc:'When page is fully interactive. < 3.8s.' },
];

/* ══════════════════════════════════════════════════════════════════
   CWV HELPERS
   ══════════════════════════════════════════════════════════════════ */
function cwvScoreClass(s)        { if(s===null||s===undefined) return ''; return s>=0.9?'good':s>=0.5?'average':'poor'; }
function cwvThresholdClass(v,g,p){ if(v===null||v===undefined) return 'average'; return v<=g?'good':v<=p?'average':'poor'; }
function cwvBadgeLabel(cls)      { return {good:'Good',average:'Needs Improvement',poor:'Poor'}[cls]??'—'; }

function buildScoreRing(score) {
    const r=52, cx=60, cy=60, circ=2*Math.PI*r;
    const dash=(score/100)*circ;
    const cls=score>=90?'good':score>=50?'average':'poor';
    const color={good:'#22c55e',average:'#eab308',poor:'#ef4444'}[cls];
    return `<div class="score-ring-wrap mx-auto">
        <svg width="100" height="100" viewBox="0 0 120 120">
            <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="#e5e7eb" stroke-width="10"/>
            <circle cx="${cx}" cy="${cy}" r="${r}" fill="none" stroke="${color}" stroke-width="10"
                stroke-dasharray="${dash} ${circ}" stroke-linecap="round"/>
        </svg>
        <div class="score-label score-${cls}">${score}<small>Score</small></div>
    </div>`;
}

function buildCWVHtml(d, strategy) {
    let html = `<div class="row g-3 mb-4 align-items-center">
        <div class="col-auto text-center">${buildScoreRing(d.performance_score)}<p class="mt-2 mb-0 fw-semibold small">Performance</p></div>
        <div class="col"><h6 class="fw-bold mb-1">Core Web Vitals Assessment</h6>
        <p class="text-muted small mb-0">${strategy==='mobile'?'<i class="mdi mdi-cellphone"></i> Mobile':'<i class="mdi mdi-monitor"></i> Desktop'} analysis — Google uses mobile for ranking.</p></div>
    </div><div class="row g-3">`;
    CWV_METRICS.forEach(m => {
        const metric  = d[m.key];
        const display = metric?.display ?? '—';
        const val     = metric?.value   ?? null;
        const score   = metric?.score   ?? null;
        const cls     = score!==null ? cwvScoreClass(score) : cwvThresholdClass(val, m.good, m.poor);
        const cwvTag  = m.cwv ? `<span class="badge bg-primary bg-opacity-10 text-primary ms-1" style="font-size:.62rem;">Core</span>` : '';
        html += `<div class="col-md-3 col-sm-6"><div class="vital-card h-100">
            <div class="d-flex align-items-center gap-1 mb-1"><span class="fw-semibold small">${m.abbr}</span>${cwvTag}</div>
            <div class="metric-value score-${cls}">${display}</div>
            <div class="metric-label">${m.label}</div>
            <span class="metric-badge bg-${cls}">${cwvBadgeLabel(cls)}</span>
            <p class="text-muted mt-2 mb-0" style="font-size:.7rem;">${m.desc}</p>
        </div></div>`;
    });
    return html + '</div>';
}

function flatRowToCWVData(row, strategy) {
    const KEYS = ['lcp','cls','tbt','inp','fcp','ttfb','si','tti'];
    const d = { performance_score: row[`${strategy}_performance_score`] ?? 0 };
    KEYS.forEach(m => {
        d[m] = {
            display: row[`${strategy}_${m}_display`] ?? '—',
            value:   row[`${strategy}_${m}_value`]   ?? null,
            score:   row[`${strategy}_${m}_score`]   ?? null,
        };
    });
    return d;
}

/* ══════════════════════════════════════════════════════════════════
   RANKING HELPERS — plain tables, no DataTables
   ══════════════════════════════════════════════════════════════════ */
function posClass(pos) { if(!pos) return 'pos-other'; if(pos<=3) return 'pos-top3'; if(pos<=10) return 'pos-top10'; return 'pos-other'; }
function extractDomain(url) { try { return new URL(url).hostname.replace('www.',''); } catch { return '—'; } }
function domainMatch(link, input) {
    if(!link||!input) return false;
    const n = s => s.toLowerCase().replace(/https?:\/\/(www\.)?/,'').replace(/\/$/,'').split('/')[0];
    return n(link).includes(n(input)) || n(input).includes(n(link));
}
function escapeHtml(str) {
    if(!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function buildOrganicRows(rows, competitorRows, clientDomain) {
    if (!rows.length) return '<tr><td colspan="6" class="text-center text-muted py-3">No results</td></tr>';
    return rows.map(r => {
        const isClient = domainMatch(r.link, clientDomain);
        const isComp   = competitorRows.some(c => c.link===r.link);
        const roleBadge= isClient
            ? '<span class="keyword-badge bg-success bg-opacity-10 text-success">Our Client</span>'
            : isComp ? '<span class="keyword-badge competitor-tag">Competitor</span>' : '';
        return `<tr class="${isClient?'table-success':''}">
            <td><span class="position-badge ${posClass(r.position)}">${r.position??'—'}</span></td>
            <td style="max-width:200px;"><a href="${escapeHtml(r.link||'#')}" target="_blank" class="text-decoration-none fw-medium small">${escapeHtml(r.title||'—')}</a></td>
            <td><a href="${escapeHtml(r.link||'#')}" target="_blank" class="result-link text-muted small" title="${escapeHtml(r.link||'')}">${escapeHtml(r.link||'—')}</a></td>
            <td><span class="small">${escapeHtml(r.domain||extractDomain(r.link))}</span></td>
            <td><span class="snippet-cell text-muted small" style="cursor:pointer;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"
                data-title="${escapeHtml(r.title||'')}" data-url="${escapeHtml(r.link||'')}" data-snippet="${escapeHtml(r.snippet||'')}">${escapeHtml(r.snippet||'—')}</span></td>
            <td>${roleBadge}</td>
        </tr>`;
    }).join('');
}

function buildCompetitorRows(rows) {
    if (!rows.length) return '<tr><td colspan="5" class="text-center text-muted py-3">No competitors identified</td></tr>';
    return rows.map(r => `<tr>
        <td><span class="position-badge ${posClass(r.position)}">${r.position??'—'}</span></td>
        <td style="max-width:220px;"><a href="${escapeHtml(r.link||'#')}" target="_blank" class="text-decoration-none fw-medium small">${escapeHtml(r.title||'—')}</a></td>
        <td><a href="${escapeHtml(r.link||'#')}" target="_blank" class="result-link text-muted small">${escapeHtml(r.link||'—')}</a></td>
        <td><span class="small">${escapeHtml(r.domain||extractDomain(r.link))}</span></td>
        <td><span class="keyword-badge competitor-tag">Competitor</span></td>
    </tr>`).join('');
}

/* Build one keyword block (organic + competitor plain tables) */
function buildKeywordBlock(res, clientDomain, competitorMode, competitorList) {
    const allRows    = res.data?.organic_results || [];
    const clientRow  = allRows.find(r => domainMatch(r.link, clientDomain));
    const clientPos  = clientRow?.position ?? Infinity;
    let   compRows;
    if (competitorMode==='auto')   compRows = allRows.filter(r=>r.position<clientPos&&!domainMatch(r.link,clientDomain));
    else if (competitorMode==='define') compRows = allRows.filter(r=>(competitorList||[]).some(c=>domainMatch(r.link,c)));
    else compRows = [];

    return `
        <div class="keyword-section-title">
            <i class="mdi mdi-magnify text-primary"></i>
            ${escapeHtml(res.keyword)}
            <span class="badge bg-secondary" style="font-size:.7rem;">${allRows.length} results</span>
        </div>
        <div class="issues-card mb-3">
            <div class="issues-card-header" style="font-size:.85rem;padding:12px 16px;">
                <i class="mdi mdi-magnify me-1 text-primary"></i> Organic Results
                <small>All organic search results for this keyword</small>
            </div>
            <div class="table-responsive">
                <table class="plain-table">
                    <thead><tr><th>Position</th><th>Title</th><th>URL</th><th>Domain</th><th>Snippet</th><th>Role</th></tr></thead>
                    <tbody>${buildOrganicRows(allRows, compRows, clientDomain)}</tbody>
                </table>
            </div>
        </div>
        <div class="issues-card mb-4">
            <div class="issues-card-header" style="font-size:.85rem;padding:12px 16px;">
                <i class="mdi mdi-chart-bar me-1 text-warning"></i> Competitor Analysis
                <small>Sites ranking above your client for this keyword</small>
            </div>
            <div class="table-responsive">
                <table class="plain-table">
                    <thead><tr><th>Position</th><th>Title</th><th>URL</th><th>Domain</th><th>Role</th></tr></thead>
                    <tbody>${buildCompetitorRows(compRows)}</tbody>
                </table>
            </div>
        </div>`;
}

/* ══════════════════════════════════════════════════════════════════
   RANKING STATS (computed from first session)
   ══════════════════════════════════════════════════════════════════ */
function usUpdateRankingStats(report) {
    document.getElementById('us_kwCount').textContent  = (report.keywords || []).length;
    document.getElementById('us_location').textContent = report.location || '—';
    let bestPos = Infinity, top10 = 0;
    (report.results_json || []).forEach(res => {
        const rows = res.data?.organic_results || [];
        const clientRow = rows.find(r => domainMatch(r.link, report.client_domain));
        if (clientRow) {
            if (clientRow.position < bestPos) bestPos = clientRow.position;
            if (clientRow.position <= 10) top10++;
        }
    });
    document.getElementById('us_bestPos').textContent = bestPos === Infinity ? '—' : '#' + bestPos;
    document.getElementById('us_top10').textContent   = top10;
}

/* ══════════════════════════════════════════════════════════════════
   PAGE INITIALISATION
   ══════════════════════════════════════════════════════════════════ */
let usActiveCwvStrategy = 'mobile';

document.addEventListener('DOMContentLoaded', function () {
    initSummaryPage(GROUP_DATA);
});

function initSummaryPage(group) {
    const hasRanking = (group.ranking || []).length > 0;
    const hasCwv     = (group.cwv     || []).length > 0;

    // ── Domain header ─────────────────────────────────────────────────
    const displayUrl = group.display_url ?? '';
    const domainOnly = displayUrl.replace(/https?:\/\/(www\.)?/i, '').replace(/\/$/, '');
    document.getElementById('us_domain').textContent = domainOnly || '—';
    document.getElementById('us_meta').textContent   =
        `${displayUrl}` +
        `${hasRanking ? ' · ' + group.ranking.length + ' ranking session(s)' : ''}` +
        `${hasCwv     ? ' · ' + group.cwv.length     + ' CWV session(s)'     : ''}`;

    if (!hasRanking && !hasCwv) {
        document.getElementById('us_emptyState').style.display = '';
        document.getElementById('us_section_reporting').style.display = 'none';
        return;
    }

    // ══ RANKING ═══════════════════════════════════════════════════════
    if (hasRanking) {
        document.getElementById('us_section_ranking').style.display = '';
        const rankingContent = document.getElementById('us_rankingContent');
        rankingContent.innerHTML = '';

        // Stats from first session
        usUpdateRankingStats(group.ranking[0]);

        group.ranking.forEach((report, idx) => {
            const dateLabel = new Date(report.created_at)
                .toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' });

            let keywordsHtml = '';
            (report.results_json || []).forEach(res => {
                keywordsHtml += buildKeywordBlock(
                    res,
                    report.client_domain || '',
                    report.competitor_option || '',
                    []
                );
            });
            if (!keywordsHtml) keywordsHtml = '<div class="empty-state py-3"><p class="mb-0">No keyword results for this session.</p></div>';

            const block = document.createElement('div');
            block.className = 'date-block';
            block.innerHTML = `
                <div class="date-heading">
                    <i class="mdi mdi-calendar-range"></i>
                    ${dateLabel}
                    <span class="badge ms-2" style="background:#dbeafe;color:#1e40af;font-size:.65rem;font-weight:600;">${(report.keywords||[]).length} kw</span>
                </div>
                ${keywordsHtml}`;
            rankingContent.appendChild(block);
        });
    }

    // ══ CORE WEB VITALS ═══════════════════════════════════════════════
    if (hasCwv) {
        document.getElementById('us_section_cwv').style.display = '';
        const cwvContent = document.getElementById('us_cwvContent');
        cwvContent.innerHTML = '';

        group.cwv.forEach((row, idx) => {
            const dateLabel = new Date(row.created_at)
                .toLocaleDateString('en-IN', { day:'2-digit', month:'short', year:'numeric' });

            // Build vitals for both strategies
            const mobileData  = flatRowToCWVData(row, 'mobile');
            const desktopData = flatRowToCWVData(row, 'desktop');

            // Build issues rows
            const buildIssueRows = (strategy) => {
                let rows = '';
                CWV_METRICS.forEach(m => {
                    const score = row[`${strategy}_${m.key}_score`];
                    if (score === null || score === undefined || score >= 0.9) return;
                    const cls    = score >= 0.5 ? 'average' : 'poor';
                    const disp   = row[`${strategy}_${m.key}_display`] ?? '—';
                    const sevCls = cls === 'poor' ? 'sev-critical' : 'sev-high';
                    const sevTxt = cls === 'poor' ? 'High' : 'Medium';
                    rows += `<tr>
                        <td>${m.label}</td>
                        <td><span class="text-muted">${m.abbr}</span></td>
                        <td><strong>${disp}</strong></td>
                        <td><span class="${sevCls}">${sevTxt}</span></td>
                    </tr>`;
                });
                return rows || '<tr><td colspan="4" class="text-center text-muted py-3">No issues detected 🎉</td></tr>';
            };

            const mobileScore  = row['mobile_performance_score']  ?? '—';
            const desktopScore = row['desktop_performance_score'] ?? '—';

            const blockId = `cwvBlock_${idx}`;
            const block   = document.createElement('div');
            block.className = 'date-block';
            block.innerHTML = `
                <div class="date-heading">
                    <i class="mdi mdi-calendar-range"></i>
                    ${dateLabel}
                    <span class="badge ms-2" style="background:#dcfce7;color:#15803d;font-size:.65rem;font-weight:600;">
                        <i class="mdi mdi-cellphone" style="font-size:.7rem;"></i> ${mobileScore}
                    </span>
                    <span class="badge ms-1" style="background:#dbeafe;color:#1e40af;font-size:.65rem;font-weight:600;">
                        <i class="mdi mdi-monitor" style="font-size:.7rem;"></i> ${desktopScore}
                    </span>
                </div>

                {{-- Mobile --}}
                <div class="issues-card mb-3">
                    <div class="issues-card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <i class="mdi mdi-cellphone me-1"></i> Mobile Performance
                            <small>Google uses mobile scores for ranking signals</small>
                        </div>
                    </div>
                    <div class="p-3">
                        ${buildCWVHtml(mobileData, 'mobile')}
                        <div class="d-flex gap-4 mt-4 flex-wrap">
                            <span><span class="legend-dot" style="background:#22c55e;"></span> Good</span>
                            <span><span class="legend-dot" style="background:#eab308;"></span> Needs Improvement</span>
                            <span><span class="legend-dot" style="background:#ef4444;"></span> Poor</span>
                        </div>
                    </div>
                </div>

                {{-- Desktop --}}
                <div class="issues-card mb-3">
                    <div class="issues-card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <i class="mdi mdi-monitor me-1"></i> Desktop Performance
                            <small>Desktop scores for additional context</small>
                        </div>
                    </div>
                    <div class="p-3">
                        ${buildCWVHtml(desktopData, 'desktop')}
                        <div class="d-flex gap-4 mt-4 flex-wrap">
                            <span><span class="legend-dot" style="background:#22c55e;"></span> Good</span>
                            <span><span class="legend-dot" style="background:#eab308;"></span> Needs Improvement</span>
                            <span><span class="legend-dot" style="background:#ef4444;"></span> Poor</span>
                        </div>
                    </div>
                </div>

                {{-- Issues --}}
                <div class="issues-card mb-4">
                    <div class="issues-card-header">
                        Most common issues
                        <small>Metrics below the recommended threshold (Mobile)</small>
                    </div>
                    <table class="issues-table">
                        <thead><tr><th>Issue</th><th>Category</th><th>Value</th><th>Severity</th></tr></thead>
                        <tbody>${buildIssueRows('mobile')}</tbody>
                    </table>
                </div>`;
            cwvContent.appendChild(block);
        });
    }
}

/* ── Snippet modal (ranking tables) ── */
document.addEventListener('click', function (e) {
    const cell = e.target.closest('.snippet-cell');
    if (!cell) return;
    document.getElementById('snippetModalTitle').textContent = cell.dataset.title || '—';
    document.getElementById('snippetModalUrl').innerHTML     = `<a href="${cell.dataset.url}" target="_blank" class="small">${cell.dataset.url}</a>`;
    document.getElementById('snippetModalBody').textContent  = cell.dataset.snippet || 'No description available.';
    new bootstrap.Modal(document.getElementById('snippetModal')).show();
});

/* ══════════════════════════════════════════════════════════════════
   PDF EXPORT — Captures all three sections into one PDF
   ══════════════════════════════════════════════════════════════════ */
document.getElementById('downloadPdfBtn').addEventListener('click', async function () {
    const button = this;
    const originalText = button.innerHTML;
    
    button.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Preparing PDF...';
    button.disabled = true;
    
    try {
        const element = document.querySelector('.seo-wrap');
        if (!element) throw new Error('Content not found');
        
        // Clone the element for PDF generation to avoid affecting the original
        const clone = element.cloneNode(true);
        clone.style.position = 'absolute';
        clone.style.top = '-9999px';
        clone.style.left = '-9999px';
        clone.style.width = element.offsetWidth + 'px';
        clone.style.backgroundColor = '#ffffff';
        document.body.appendChild(clone);
        
        // Use html2canvas with optimized settings
        const canvas = await html2canvas(clone, {
            scale: 3, // Higher quality
            backgroundColor: '#ffffff',
            useCORS: true,
            logging: false,
            windowWidth: clone.scrollWidth,
            windowHeight: clone.scrollHeight
        });
        
        // Remove clone
        document.body.removeChild(clone);
        
        // Create PDF
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({
            unit: 'mm',
            format: 'a4',
            orientation: 'portrait',
            compress: true
        });
        
        const imgData = canvas.toDataURL('image/png', 1.0);
        const imgWidth = pdf.internal.pageSize.getWidth();
        const imgHeight = (canvas.height * imgWidth) / canvas.width;
        
        // Calculate pages
        let heightLeft = imgHeight;
        let position = 0;
        let pageNum = 1;
        
        // First page
        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight, undefined, 'FAST');
        heightLeft -= pdf.internal.pageSize.getHeight();
        
        // Additional pages
        while (heightLeft > 0) {
            position = heightLeft - imgHeight;
            pdf.addPage();
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight, undefined, 'FAST');
            heightLeft -= pdf.internal.pageSize.getHeight();
            pageNum++;
            
            // Update progress for large reports
            button.innerHTML = `<i class="mdi mdi-loading mdi-spin"></i> Processing page ${pageNum}...`;
            await new Promise(resolve => setTimeout(resolve, 50));
        }
        
        // Generate filename
        const domain = document.getElementById('us_domain')?.textContent.trim() || 'report';
        const date = new Date().toISOString().slice(0, 10);
        pdf.save(`SEO_Report_${domain}_${date}.pdf`);
        
        button.innerHTML = '<i class="mdi mdi-check-circle"></i> Downloaded!';
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 2000);
        
    } catch (error) {
        console.error('PDF Error:', error);
        button.innerHTML = '<i class="mdi mdi-close-circle"></i> Failed!';
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = false;
        }, 3000);
        alert('Unable to generate PDF. The page might contain external resources blocking capture.');
    }
});
</script>
@endpush