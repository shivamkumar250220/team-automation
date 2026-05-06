@extends('layouts.app')

@section('title', 'SEO Tools')
@section('page_header', 'SEO Tools')
@section('page_icon', 'mdi mdi-magnify')

@section('breadcrumb')
    <li class="breadcrumb-item active">SEO</li>
    <li class="breadcrumb-item active" aria-current="page">SEO Tools</li>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
/* ══════════════════════════════════════════════════════════════
   SEO DASHBOARD — Global Layout
   ══════════════════════════════════════════════════════════════ */
.seo-wrap {
    background: #f6f8fa;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
}

/* ── Top nav bar (like SEO Audit header) ─── */
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
.seo-nav-link {
    padding: 14px 18px; font-size: 0.9rem; font-weight: 500;
    color: #6b7280; border: none; background: none; cursor: pointer;
    border-bottom: 2px solid transparent; transition: all .15s;
    white-space: nowrap;
}
.seo-nav-link:hover  { color: #111827; }
.seo-nav-link.active { color: #111827; border-bottom-color: #111827; font-weight: 600; }

/* ── Content panes ─── */
.seo-pane { padding: 32px 28px; background: #fff; min-height: 420px; }

/* ── Tool selector card (like Single page / Full site radio) ─── */
.tool-selector-card {
    border: 1px solid #e5e7eb; border-radius: 12px;
    padding: 24px 28px; margin-bottom: 28px;
    background: #fff;
}
.tool-radio-row {
    display: flex; gap: 8px; flex-wrap: wrap;
    margin-bottom: 20px;
}
.tool-radio-btn {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 18px; border-radius: 8px;
    border: 1.5px solid #d1d5db; background: #f9fafb;
    cursor: pointer; font-size: 0.875rem; font-weight: 500;
    transition: all .15s; user-select: none;
}
.tool-radio-btn input[type=radio] { accent-color: #16a34a; width: 16px; height: 16px; }
.tool-radio-btn.selected {
    border-color: #16a34a; background: #f0fdf4; color: #15803d;
}

/* ── Form area ─── */
#formArea .form-label { font-weight: 500; font-size: .875rem; }
.seo-submit-btn {
    background: #16a34a; color: #fff; border: none;
    border-radius: 8px; padding: 12px 0;
    font-size: 1rem; font-weight: 600; width: 100%;
    max-width: 440px; cursor: pointer; transition: background .15s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
}
.seo-submit-btn:hover:not(:disabled) { background: #15803d; }
.seo-submit-btn:disabled { opacity: .6; cursor: not-allowed; }

/* ── Results list (like "Recently audited URLs") ─── */
.results-pane { padding: 28px; background: #fff; }
.results-pane h4 { font-size: 1.1rem; font-weight: 700; color: #111827; margin-bottom: 4px; }
.results-toolbar {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 18px; flex-wrap: wrap;
}
.results-toolbar input {
    border: 1px solid #d1d5db; border-radius: 8px;
    padding: 6px 14px; font-size: .85rem; flex: 1; max-width: 340px;
}
.results-toolbar .filter-btns { display: flex; gap: 4px; }
.results-toolbar .filter-btn {
    border: 1px solid #d1d5db; background: #f9fafb;
    border-radius: 6px; padding: 5px 14px; font-size: .8rem;
    cursor: pointer; font-weight: 500; transition: all .15s;
}
.results-toolbar .filter-btn.active { background: #111827; color: #fff; border-color: #111827; }
.result-row {
    display: flex; align-items: center; gap: 16px;
    padding: 14px 20px; border-bottom: 1px solid #f1f5f9;
    transition: background .1s; flex-wrap: wrap;
}
.result-row:last-child { border-bottom: none; }
.result-row:hover { background: #f8fafc; }
.result-domain {
    font-size: .95rem; font-weight: 700; color: #111827;
    min-width: 200px; flex: 1;
}
.result-domain .result-meta {
    font-size: .78rem; font-weight: 400; color: #9ca3af; margin-left: 8px;
}
.result-domain .tool-pill {
    font-size: .68rem; font-weight: 600; padding: 2px 8px;
    border-radius: 20px; margin-left: 8px; vertical-align: middle;
}
.pill-ranking  { background: #ede9fe; color: #6d28d9; }
.pill-cwv      { background: #dbeafe; color: #1e40af; }
.pill-report   { background: #fef3c7; color: #92400e; }

.score-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 42px; height: 28px; border-radius: 6px;
    font-size: .82rem; font-weight: 700; padding: 0 10px;
}
.score-green  { background: #dcfce7; color: #15803d; }
.score-yellow { background: #fef9c3; color: #b45309; }
.score-red    { background: #fee2e2; color: #b91c1c; }
.score-gray   { background: #f3f4f6; color: #6b7280; }

.issue-pill {
    display: inline-flex; align-items: center;
    padding: 3px 10px; border-radius: 20px;
    font-size: .75rem; font-weight: 600; white-space: nowrap;
}
.pill-critical { background: #fee2e2; color: #b91c1c; }
.pill-high     { background: #fef3c7; color: #b45309; }
.pill-clean    { background: #dcfce7; color: #15803d; }

.view-summary-btn {
    border: 1px solid #d1d5db; background: #fff;
    border-radius: 8px; padding: 6px 16px;
    font-size: .82rem; font-weight: 500; cursor: pointer;
    transition: all .15s; white-space: nowrap; color: #374151;
}
.view-summary-btn:hover { background: #f9fafb; border-color: #9ca3af; }

/* ══════════════════════════════════════════════════════════════
   SUMMARY VIEW (Images 3 & 4)
   ══════════════════════════════════════════════════════════════ */
.summary-pane { padding: 28px; background: #f6f8fa; min-height: 500px; }

.summary-header-card {
    background: #fff; border: 1px solid #e5e7eb;
    border-radius: 12px; padding: 24px 28px; margin-bottom: 20px;
}
.summary-domain-title { font-size: 1.4rem; font-weight: 800; color: #111827; }
.summary-meta { font-size: .8rem; color: #9ca3af; margin-top: 4px; }

.summary-stats-grid {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 1px; background: #e5e7eb;
    border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;
    margin-top: 20px;
}
.stat-box {
    background: #fff; padding: 20px 24px;
    display: flex; flex-direction: column; align-items: flex-start;
}
.stat-box .stat-label { font-size: .7rem; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; }
.stat-box .stat-value { font-size: 2.2rem; font-weight: 800; color: #111827; line-height: 1; }
.stat-box .stat-sub   { font-size: .8rem; color: #9ca3af; margin-top: 4px; }
.stat-box .stat-value.green { color: #16a34a; }
.stat-box .stat-value.red   { color: #dc2626; }

/* Score ring (shared with CWV) */
.score-ring-wrap { position: relative; width: 100px; height: 100px; }
.score-ring-wrap svg { transform: rotate(-90deg); }
.score-ring-wrap .score-label {
    position: absolute; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    font-size: 1.4rem; font-weight: 700; line-height: 1;
}
.score-ring-wrap .score-label small { font-size: .6rem; font-weight: 500; color: #6b7280; margin-top: 2px; }
.score-good    { color: #15803d; } .score-average { color: #b45309; } .score-poor { color: #b91c1c; }

/* Issues section */
.issues-card {
    background: #fff; border: 1px solid #e5e7eb;
    border-radius: 12px; overflow: hidden; margin-bottom: 16px;
}
.issues-card-header {
    padding: 16px 24px; border-bottom: 1px solid #f1f5f9;
    font-weight: 700; font-size: .95rem; color: #111827;
}
.issues-card-header small { display: block; font-weight: 400; font-size: .78rem; color: #9ca3af; margin-top: 2px; }
.issues-table { width: 100%; border-collapse: collapse; }
.issues-table th { padding: 10px 20px; text-align: left; font-size: .78rem; font-weight: 600; color: #6b7280; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
.issues-table td { padding: 12px 20px; font-size: .875rem; border-bottom: 1px solid #f1f5f9; color: #374151; }
.issues-table tr:last-child td { border-bottom: none; }
.issues-table tr:hover td { background: #f9fafb; }
.sev-high     { color: #b45309; font-weight: 600; }
.sev-medium   { color: #9ca3af; font-weight: 600; }
.sev-critical { color: #b91c1c; font-weight: 600; }
.sev-low      { color: #6b7280; font-weight: 600; }

/* Back button */
.back-btn {
    display: inline-flex; align-items: center; gap: 6px;
    color: #6b7280; font-size: .875rem; cursor: pointer;
    border: none; background: none; padding: 0; margin-bottom: 20px;
    font-weight: 500;
}
.back-btn:hover { color: #111827; }

/* ── Summary type tabs (Ranking / Core Web Vitals / Reporting) ─── */
.summary-type-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 20px; }
.summary-type-tab {
    border: 1px solid #d1d5db; background: #f9fafb;
    border-radius: 6px; padding: 5px 18px;
    font-size: .875rem; font-weight: 500; cursor: pointer;
    transition: all .15s; color: #374151;
}
.summary-type-tab:hover:not(.active) { background: #f3f4f6; }
.summary-type-tab.active { background: #111827; color: #fff; border-color: #111827; }

/* ── CWV specific ─── */
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
.ring-good  { stroke: #22c55e; } .ring-average { stroke: #eab308; } .ring-poor { stroke: #ef4444; }
.legend-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 4px; }
.strategy-tab-btn {
    border: 1px solid #d1d5db; background: #f9fafb;
    border-radius: 8px; padding: 6px 20px;
    font-weight: 600; cursor: pointer; transition: all .15s; font-size: .85rem;
}
.strategy-tab-btn.active { background: #1e40af; color: #fff; border-color: #1e40af; }

/* ── Ranking specific ─── */
.keyword-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: .75rem; font-weight: 600; margin-right: 4px; }
.position-badge { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; font-weight: 700; font-size: .78rem; }
.pos-top3  { background: #d1fae5; color: #065f46; }
.pos-top10 { background: #dbeafe; color: #1e40af; }
.pos-other { background: #f3f4f6; color: #6b7280; }
.result-link { max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.competitor-tag { background: #fef3c7; color: #92400e; }

/* ── Reporting sheet dashboard ─── */
#dashboardTable { width: 100%; border-collapse: collapse; }
#dashboardTable th, #dashboardTable td { border: 1px solid #dee2e6; padding: 8px 12px; font-size: .875rem; }
#dashboardTable thead th { background-color: #1e3a5f; color: #fff; text-align: center; font-weight: 600; }
#dashboardTable tbody tr:nth-child(even) { background: #f8f9fa; }
#dashboardTable tbody td:first-child { text-align: center; width: 60px; }
#dashboardTable a { color: #1a56db; word-break: break-all; }
.table-title-row td { background-color: #1e3a5f !important; color: #fff; text-align: center; font-weight: 700; font-size: 1rem; }

/* ── XML drop zone ─── */
#xmlDropZone { border-color: #ced4da!important; cursor: pointer; transition: background .2s; }

/* ── Misc ─── */
.save-status { font-size: .8rem; }
.empty-state { text-align: center; padding: 60px 20px; color: #9ca3af; }
.empty-state i { font-size: 3rem; margin-bottom: 12px; display: block; }

@media (max-width: 640px) {
    .summary-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .seo-topbar { padding: 0 16px; }
    .seo-pane, .results-pane, .summary-pane { padding: 20px 16px; }
}
/* ══ AI Overview Panel ═══════════════════════════════════════════ */
.aio-wrap {
    border: 1.5px solid #bfdbfe;
    border-radius: 12px;
    background: #f0f7ff;
    margin-bottom: 22px;
    overflow: hidden;
}
.aio-header {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    padding: 12px 18px;
    background: #fff;
    border-bottom: 1px solid #dbeafe;
}
.aio-google-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: #1a73e8; color: #fff;
    border-radius: 20px; padding: 3px 11px;
    font-size: .72rem; font-weight: 700; letter-spacing: .02em;
    flex-shrink: 0;
}
.aio-detected-pill  { background:#dcfce7; color:#15803d; border-radius:6px; padding:2px 10px; font-size:.72rem; font-weight:600; }
.aio-absent-pill    { background:#f3f4f6; color:#6b7280; border-radius:6px; padding:2px 10px; font-size:.72rem; font-weight:600; }
.aio-client-pill    { background:#dcfce7; color:#15803d; border-radius:6px; padding:2px 10px; font-size:.72rem; font-weight:600; }
.aio-noclient-pill  { background:#fee2e2; color:#b91c1c; border-radius:6px; padding:2px 10px; font-size:.72rem; font-weight:600; }
.aio-comp-pill      { background:#fef3c7; color:#92400e; border-radius:6px; padding:2px 10px; font-size:.72rem; font-weight:600; }
.aio-body           { padding: 16px 18px; }
.aio-answer-box {
    background: #fff;
    border: 1px solid #dbeafe;
    border-left: 4px solid #1a73e8;
    border-radius: 0 8px 8px 0;
    padding: 12px 16px;
    margin-bottom: 16px;
    font-size: .875rem;
    color: #1e293b;
    line-height: 1.65;
}
.aio-answer-box strong { color: #1a73e8; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; display: block; margin-bottom: 6px; }
.aio-sources-label  { font-size: .7rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 8px; }
.aio-source-row {
    display: flex; align-items: center; gap: 9px;
    padding: 8px 12px; border-radius: 8px; margin-bottom: 5px;
    border: 1px solid #e5e7eb; background: #fff;
    font-size: .83rem; transition: box-shadow .12s;
}
.aio-source-row:last-child { margin-bottom: 0; }
.aio-source-row:hover      { box-shadow: 0 2px 8px rgba(0,0,0,.07); }
.aio-source-row.aio-is-client     { border-color: #16a34a; background: #f0fdf4; }
.aio-source-row.aio-is-competitor { border-color: #d97706; background: #ffe0bb; }
.aio-source-row.aio-is-low-competitor { border-color: #fac280; background: #fffbeb; }
.aio-source-favicon { width: 14px; height: 14px; border-radius: 2px; flex-shrink: 0; }
.aio-source-title   { flex: 1; font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.aio-source-domain  { font-size: .75rem; color: #9ca3af; flex-shrink: 0; }
.aio-role-badge     { flex-shrink: 0; border-radius: 4px; padding: 1px 8px; font-size: .7rem; font-weight: 600; white-space: nowrap; }
.aio-role-client    { background: #dcfce7; color: #15803d; }
.aio-role-comp      { background: #fef3c7; color: #92400e; }
/* Highlight competitor rows in the organic table when in AIO */
.aio-in-overview td { background: rgba(251, 191, 36, 0.08) !important; }
.aio-in-overview td:first-child { border-left: 3px solid #f59e0b; }
.aio-overview-tag   { background: #fef3c7; color: #92400e; border-radius: 4px; padding: 1px 7px; font-size: .68rem; font-weight: 700; margin-left: 4px; vertical-align: middle; }
/* Client highlighted in AIO */
.aio-client-in-overview td { background: rgba(22, 163, 74, 0.07) !important; }
.aio-client-in-overview td:first-child { border-left: 3px solid #16a34a; }

/* ── Competitor-in-AIO spotlight block (inside AIO panel) ────── */
.aio-comp-spotlight {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border: 2px solid #f59e0b;
    border-radius: 10px;
    padding: 14px 16px;
    margin-top: 14px;
}
.aio-comp-spotlight-header {
    display: flex; align-items: center; gap: 7px;
    font-size: .72rem; font-weight: 700; color: #92400e;
    text-transform: uppercase; letter-spacing: .06em;
    margin-bottom: 10px;
}
.aio-comp-cited-item {
    display: flex; align-items: flex-start; gap: 10px;
    background: #fff; border: 1px solid #fde68a;
    border-radius: 8px; padding: 10px 12px;
    margin-bottom: 7px;
}
.aio-comp-cited-item:last-child { margin-bottom: 0; }
.aio-comp-cited-body { flex: 1; min-width: 0; }
.aio-comp-cited-title {
    font-weight: 600; font-size: .85rem; color: #1e293b;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.aio-comp-cited-href {
    font-size: .72rem; color: #6b7280; margin-top: 2px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.aio-comp-cited-domain-badge {
    flex-shrink: 0; background: #fef3c7; color: #92400e;
    border-radius: 4px; padding: 2px 8px;
    font-size: .7rem; font-weight: 700; white-space: nowrap;
    margin-top: 2px;
}
.aio-comp-spotlight-tip {
    font-size: .72rem; color: #92400e; margin-top: 10px;
    display: flex; align-items: flex-start; gap: 5px;
    line-height: 1.5;
}
/* Competitor table — richer AIO cell */
.aio-comp-table-cell { display: flex; flex-direction: column; gap: 4px; }
.aio-comp-table-badge {
    display: inline-flex; align-items: center; gap: 4px;
    background: #f59e0b; color: #fff;
    border-radius: 6px; padding: 2px 10px;
    font-size: .72rem; font-weight: 700; width: fit-content;
}
.aio-comp-table-cited {
    font-size: .72rem; color: #92400e; font-style: italic;
    max-width: 200px; overflow: hidden; text-overflow: ellipsis;
    white-space: nowrap; cursor: default;
}
/* Strengthen the row highlight for AIO competitors */
.aio-in-overview td { background: rgba(245, 158, 11, 0.13) !important; }
.aio-in-overview td:first-child { border-left: 4px solid #f59e0b !important; }

/* ── AI Overview Panel ──────────────────────────────────────── */
.aio-panel {
    border: 1.5px solid #bfdbfe;
    border-radius: 12px;
    background: #f8faff;
    margin-bottom: 24px;
    overflow: hidden;
}
.aio-panel-header {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    padding: 14px 20px;
    border-bottom: 1px solid #e0eaff;
    background: #fff;
}
.aio-google-chip {
    display: inline-flex; align-items: center; gap: 5px;
    background: #1a73e8; color: #fff;
    border-radius: 20px; padding: 3px 11px;
    font-size: .72rem; font-weight: 700; letter-spacing: .02em;
}
.aio-status-found   { background: #dcfce7; color: #15803d; border-radius: 6px; padding: 2px 10px; font-size: .75rem; font-weight: 600; }
.aio-status-missing { background: #fee2e2; color: #b91c1c; border-radius: 6px; padding: 2px 10px; font-size: .75rem; font-weight: 600; }
.aio-status-none    { background: #f3f4f6; color: #6b7280; border-radius: 6px; padding: 2px 10px; font-size: .75rem; font-weight: 600; }
.aio-summary-chip   { border-radius: 6px; padding: 2px 10px; font-size: .72rem; font-weight: 600; }
.aio-panel-body     { padding: 14px 20px; }
.aio-text-quote {
    border-left: 3px solid #93c5fd; padding: 6px 12px;
    font-size: .83rem; color: #374151; background: #fff;
    border-radius: 0 6px 6px 0; margin-bottom: 12px;
    line-height: 1.5;
}
.aio-sources-label  { font-size: .72rem; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; }
.aio-source-row {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 12px; border-radius: 8px; margin-bottom: 5px;
    border: 1px solid #e5e7eb; background: #fff;
    font-size: .83rem; transition: box-shadow .1s;
}
.aio-source-row:hover { box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.aio-source-row.is-client   { border-color: #16a34a; background: #f0fdf4; }
.aio-source-row.is-competitor { border-color: #f59e0b; background: #fffbeb; }
.aio-favicon { width: 14px; height: 14px; border-radius: 2px; flex-shrink: 0; }
.aio-no-aio {
    padding: 14px 20px; display: flex; align-items: center; gap: 8px;
    color: #9ca3af; font-size: .85rem;
}
/* ── AI Overview section ─── */
.ai-overview-card {
    background: linear-gradient(135deg, #f0f9ff 0%, #e8f5e9 100%);
    border: 1.5px solid #93c5fd;
    border-radius: 12px;
    padding: 18px 22px;
    margin-bottom: 24px;
}
.ai-overview-card .ai-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: #1a73e8; color: #fff;
    border-radius: 20px; padding: 3px 12px;
    font-size: .75rem; font-weight: 700;
    margin-bottom: 10px;
}
.ai-overview-domain-row {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 10px; border-radius: 8px;
    margin-bottom: 4px; background: #fff;
    border: 1px solid #e5e7eb; font-size: .85rem;
}
.ai-overview-domain-row.is-client { border-color: #16a34a; background: #f0fdf4; }
.ai-overview-domain-row.is-competitor { border-color: #f59e0b; background: #fffbeb; }
.ai-ov-not-found {
    color: #9ca3af; font-size: .85rem;
    display: flex; align-items: center; gap: 6px;
}

</style>
@endpush

@section('content')
<div class="row g-4">
<div class="col-12">
<div class="seo-wrap card p-0 overflow-hidden">

    {{-- ══════════════════════════════════════════════════════════
         TOP NAV BAR
    ═══════════════════════════════════════════════════════════ --}}
    <div class="seo-topbar">
        <div class="brand">
            <div class="brand-dot"><i class="mdi mdi-check"></i></div>
            SEO Tools
        </div>
        <button class="seo-nav-link active" id="navNewAudit"  onclick="showView('newAudit')">New analysis</button>
        <button class="seo-nav-link"        id="navResults"   onclick="showView('results')">Saved results</button>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         VIEW 1 — NEW AUDIT FORM
    ═══════════════════════════════════════════════════════════ --}}
    <div id="viewNewAudit" class="seo-pane">

        <h4 style="font-size:1.45rem;font-weight:800;color:#111827;margin-bottom:6px;">
            Run a new SEO analysis
        </h4>
        <p class="text-muted mb-4" style="font-size:.9rem;">
            Analyse rankings, core web vitals, hygiene site audit or generate a reporting sheet for your client.
        </p>

        {{-- ── Tool selector ── --}}
        <div class="tool-selector-card">

            <div class="tool-radio-row" id="toolRadioRow">
                <label class="tool-radio-btn selected" id="lbl-ranking">
                    <input type="radio" name="seoTool" value="ranking" checked onchange="switchTool(this)">
                    <i class="mdi mdi-chart-bar text-primary"></i> Ranking &amp; Competitor Report
                </label>
                <label class="tool-radio-btn" id="lbl-cwv">
                    <input type="radio" name="seoTool" value="cwv" onchange="switchTool(this)">
                    <i class="mdi mdi-speedometer" style="color:#6d28d9;"></i> Core Web Vitals
                </label>
                <label class="tool-radio-btn" id="lbl-reporting">
                    <input type="radio" name="seoTool" value="reporting" onchange="switchTool(this)">
                    <i class="mdi mdi-file-chart" style="color:#b45309;"></i> Reporting Sheet
                </label>
                <label class="tool-radio-btn" id="lbl-website_audit">
                    <input type="radio" name="seoTool" value="website_audit" onchange="switchTool(this)">
                    <i class="mdi mdi-file-chart" style="color:#b45309;"></i> Hygiene Site Audit
                </label>
            </div>

            {{-- ══════════ FORM: RANKING ══════════ --}}
            <div id="formRanking" class="tool-form">
                <form id="rankingForm" novalidate>
                    @csrf
                    <input type="hidden" name="created_by_user_id" value="{{ $created_by_user_id }}">
                    <input type="hidden" name="client_property_id"  value="{{ $client_property_id }}">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Location <span class="text-danger">*</span></label>
                            <select class="form-select" id="r_locationSelect" name="location" required>
                                <option value="" disabled selected>Select location</option>
                                <option value="Delhi">Delhi</option>
                                <option value="Gurgaon">Gurgaon</option>
                                <option value="Noida">Noida</option>
                                <option value="Faridabad">Faridabad</option>
                                <option value="Ghaziabad">Ghaziabad</option>
                            </select>
                            <div class="invalid-feedback">Please select a location.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Keywords <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="r_keywordsInput" name="keywords"
                                placeholder="Comma-separated keywords" required>
                            <small class="text-muted">Separate keywords with a comma</small>
                            <div class="invalid-feedback">Please enter at least one keyword.</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Our Client <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="r_ourclientInput" name="ourclient"
                                placeholder="Enter client domain" value="{{ $domain }}" required>
                            <div class="invalid-feedback">Please enter the client domain.</div>
                        </div>
                        <div class="col-12">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="competitorOption" id="r_defineCompetitor" value="define">
                                        <label class="form-check-label fw-semibold" for="r_defineCompetitor">Define Competitor Domain</label>
                                    </div>
                                    <div id="r_competitorDomainsInput" style="display:none;">
                                        <input type="text" class="form-control" id="r_competitorUrls" name="competitor_urls"
                                            placeholder="Comma-separated competitor URLs">
                                        <small class="text-muted">Separate each URL with a comma</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="radio" name="competitorOption" id="r_autoCheckCompetitor" value="auto">
                                        <label class="form-check-label fw-semibold" for="r_autoCheckCompetitor">Auto-detect competitors</label>
                                        <p class="small text-muted mt-1 mb-0">System automatically detects competitors from keyword results.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 mt-4">
                        <button type="submit" class="seo-submit-btn" id="r_generateBtn" style="width:auto;padding:10px 28px;">
                            <span id="r_generateBtnText">Generate Report</span>
                            <span id="r_generateBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                        <button type="reset" class="btn btn-light" id="r_resetBtn">Reset</button>
                        <span id="r_saveStatus" class="save-status d-none"></span>
                    </div>
                </form>
            </div>

            {{-- ══════════ FORM: CORE WEB VITALS ══════════ --}}
            <div id="formCWV" class="tool-form" style="display:none;">
                <form id="cwvForm" novalidate>
                    @csrf
                    <input type="hidden" name="created_by_user_id" value="{{ $created_by_user_id }}">
                    <input type="hidden" name="client_property_id"  value="{{ $client_property_id }}">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Website URL <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="c_ourclientInput" name="ourclient"
                                placeholder="https://example.com" value="{{ $domain }}" required>
                            <div class="invalid-feedback">Please enter a valid URL (include https://).</div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div>
                                <div class="form-text text-muted mb-2">
                                    <i class="mdi mdi-information-outline"></i>
                                    Both mobile and desktop scores are fetched via Google PageSpeed Insights.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 mt-4">
                        <button type="submit" class="seo-submit-btn" id="c_generateBtn" style="width:auto;padding:10px 28px;">
                            <span id="c_generateBtnText">Analyse Web Vitals</span>
                            <span id="c_generateBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                        <button type="reset" class="btn btn-light" id="c_resetBtn">Reset</button>
                    </div>
                </form>
            </div>

            {{-- ══════════ FORM: REPORTING SHEET ══════════ --}}
            <div id="formReporting" class="tool-form" style="display:none;">
                <form id="reportingForm" novalidate>
                    @csrf
                    <input type="hidden" name="created_by_user_id" value="{{ $created_by_user_id }}">
                    <input type="hidden" name="client_property_id"  value="{{ $client_property_id }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Our Client <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="p_ourclientInput" name="ourclient"
                                placeholder="Enter client domain" value="{{ $domain }}" required>
                            <div class="invalid-feedback">Please enter the client domain.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Google Spreadsheet URL <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="p_spreadsheetUrl" name="spreadsheet_url"
                                placeholder="https://docs.google.com/spreadsheets/d/..." required>
                            <div class="invalid-feedback">Please enter a valid Google Spreadsheet URL.</div>
                            <div class="form-text text-muted mt-1">
                                <i class="mdi mdi-information-outline"></i>
                                Ensure you have edit access to this spreadsheet.
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">
                                Sitemap XML Files
                                <span class="badge bg-secondary ms-1" style="font-size:.7rem;">Optional</span>
                            </label>
                            <div id="p_xmlDropZone"
                                 class="border border-2 border-dashed rounded p-4 text-center"
                                 style="border-color:#ced4da!important; cursor:pointer; transition:background .2s;"
                                 ondragover="event.preventDefault(); this.style.background='#eef4ff';"
                                 ondragleave="this.style.background='';"
                                 ondrop="handleXmlDrop(event)">
                                <i class="mdi mdi-file-xml-box fs-2 text-muted"></i>
                                <p class="mb-1 text-muted">Drag &amp; drop <strong>.xml</strong> files here, or</p>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="$('#p_xmlFilesInput').click()">
                                    Browse files
                                </button>
                                <input type="file" id="p_xmlFilesInput" name="xml_files[]"
                                       accept=".xml,application/xml,text/xml"
                                       multiple class="d-none">
                                <p class="form-text text-muted mb-0 mt-2">
                                    Analysed for <strong>Duplicate H1</strong>, <strong>Multiple H1</strong>, and
                                    <strong>Missing Alt Text</strong> via Gemini AI.
                                </p>
                            </div>
                            <div id="p_xmlFileList" class="d-flex flex-wrap gap-2 mt-2"></div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 mt-4">
                        <button type="submit" class="seo-submit-btn" id="p_generateBtn" style="width:auto;padding:10px 28px;">
                            <span id="p_generateBtnText">Generate Reporting Sheet</span>
                            <span id="p_generateBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                        <button type="reset" class="btn btn-light" id="p_resetBtn">Reset</button>
                    </div>
                </form>
            </div>

            {{-- ══════════ FORM: Website Audit ══════════ --}}
            <div id="formWA" class="tool-form" style="display:none;">
                <form id="waForm" novalidate>
                    @csrf
                    <input type="hidden" name="created_by_user_id" value="{{ $created_by_user_id }}">
                    <input type="hidden" name="client_property_id"  value="{{ $client_property_id }}">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Website URL <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="wa_ourclientInput" name="ourclient"
                                placeholder="https://example.com" value="{{ $domain }}" required>
                            <div class="invalid-feedback">Please enter a valid URL (include https://).</div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div>
                                <div class="form-text text-muted mb-2">
                                    <i class="mdi mdi-information-outline"></i>
                                    Both Mozilla and Google PageSpeed Insights are fetched.
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3 mt-4">
                        <button type="submit" class="seo-submit-btn" id="wa_generateBtn" style="width:auto;padding:10px 28px;">
                            <span id="wa_generateBtnText">Analyse Website Health</span>
                            <span id="wa_generateBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>
                        <button type="reset" class="btn btn-light" id="wa_resetBtn">Reset</button>
                    </div>
                </form>
            </div>

        </div>{{-- /tool-selector-card --}}

        {{-- ── Error alert (live results) ── --}}
        <div id="liveErrorSection" style="display:none;" class="mb-3">
            <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
                <i class="mdi mdi-alert-circle me-2"></i>
                <span id="liveErrorMessage">An error occurred.</span>
                <button type="button" class="btn-close" onclick="document.getElementById('liveErrorSection').style.display='none';"></button>
            </div>
        </div>

        {{-- ── Live Results: RANKING ── --}}
        <div id="r_resultsSection" style="display:none;">
            <div class="card mb-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="card-title mb-0">Search Ranking &amp; Competitor Results</h5>
                            <p class="card-subtitle mt-1" id="r_resultsSubtitle"></p>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" id="r_exportCsvBtn">
                            <i class="mdi mdi-download me-1"></i> Export CSV
                        </button>
                    </div>
                    <ul class="nav nav-tabs mb-3" id="r_keywordTabs" role="tablist"></ul>
                    <div class="tab-content" id="r_keywordTabsContent"></div>
                </div>
            </div>
        </div>

        {{-- ── Live Results: CWV ── --}}
        <div id="c_resultsSection" style="display:none;">
            <div class="card mb-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
                        <div>
                            <h5 class="card-title mb-0">Results for <span id="c_resultUrl" class="text-primary"></span></h5>
                            <p class="text-muted small mt-1">Powered by Google PageSpeed Insights</p>
                        </div>
                        <div class="d-flex gap-2" id="c_strategyTabs">
                            <button class="strategy-tab-btn active" data-strategy="mobile">
                                <i class="mdi mdi-cellphone me-1"></i> Mobile
                            </button>
                            <button class="strategy-tab-btn" data-strategy="desktop">
                                <i class="mdi mdi-monitor me-1"></i> Desktop
                            </button>
                        </div>
                    </div>
                    <div id="c_vitalsContent"></div>
                    <div class="d-flex gap-4 mt-4 flex-wrap">
                        <span><span class="legend-dot" style="background:#22c55e;"></span> Good</span>
                        <span><span class="legend-dot" style="background:#eab308;"></span> Needs Improvement</span>
                        <span><span class="legend-dot" style="background:#ef4444;"></span> Poor</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Live Results: REPORTING ── --}}
        <div id="p_resultsSection" style="display:none;">
            <div class="card mb-0">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <h5 class="card-title mb-0">Reporting Dashboard</h5>
                        <a id="p_spreadsheetLink" href="#" target="_blank" class="btn btn-sm btn-success d-none">
                            <i class="mdi mdi-google-spreadsheet me-1"></i> Open in Google Sheets
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table id="dashboardTable">
                            <thead>
                                <tr class="table-title-row"><td colspan="3">Dashboard</td></tr>
                                <tr><th>S. No</th><th>Error</th><th>Doc File</th></tr>
                            </thead>
                            <tbody id="p_dashboardBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        
        {{-- ── Live Results: WEBSITE AUDIT ── --}}
        <div id="wa_resultsSection" style="display:none;">
            <div class="card mb-0">
                <div id="wa_resultsContent"></div>
            </div>
        </div>

        {{-- Snippet modal (for ranking results) --}}
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

    </div>{{-- /viewNewAudit --}}

    {{-- ══════════════════════════════════════════════════════════
         VIEW 2 — SAVED RESULTS LIST
    ═══════════════════════════════════════════════════════════ --}}
    <div id="viewResults" style="display:none;" class="results-pane">

        <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
            <h4>Recently analysed URLs</h4>
            <span class="text-muted" style="font-size:.8rem;" id="resultsCount"></span>
        </div>
        <p class="text-muted mb-4" style="font-size:.85rem;">
            Select a previous analysis to view its full summary and detailed breakdown.
        </p>

        <div class="results-toolbar">
            <input type="text" id="resultSearch" placeholder="Search URL or domain…" oninput="filterResults()">
            <div class="filter-btns">
                <button class="filter-btn active" data-filter="all"      onclick="setFilter(this,'all')">All</button>
                <button class="filter-btn"         data-filter="ranking"  onclick="setFilter(this,'ranking')">Ranking</button>
                <button class="filter-btn"         data-filter="cwv"      onclick="setFilter(this,'cwv')">Core Web Vitals</button>
                <button class="filter-btn"         data-filter="report"   onclick="setFilter(this,'report')">Reporting</button>
            </div>
        </div>

        <div id="savedResultsList">
            {{-- ══════════════════════════════════════════════════════════════
                 MERGED rows: one row per unique URL, badges for each type done
            ═══════════════════════════════════════════════════════════════ --}}
            @forelse($groupedUrls as $key => $group)
            @php
                $gUrl        = $group['display_url'];
                $hasRanking  = count($group['ranking']) > 0;
                $hasCwv      = count($group['cwv'])     > 0;

                // For display: best mobile CWV score across all CWV sessions
                $bestCwvScore = null;
                $totalCwvIssues = 0;
                foreach ($group['cwv'] as $cv) {
                    $s = $cv['mobile_performance_score'] ?? null;
                    if ($s !== null && ($bestCwvScore === null || $s > $bestCwvScore)) {
                        $bestCwvScore = $s;
                    }
                }
                if ($bestCwvScore !== null) {
                    $cwvScoreClass = $bestCwvScore >= 90 ? 'score-green' : ($bestCwvScore >= 50 ? 'score-yellow' : 'score-red');
                    $cwvKeys = ['lcp','cls','tbt','inp','fcp','ttfb','si','tti'];
                    // Count issues from the most recent CWV session
                    $latestCwv = $group['cwv'][0] ?? [];
                    foreach ($cwvKeys as $k) {
                        $sc = $latestCwv["mobile_{$k}_score"] ?? null;
                        if ($sc !== null && $sc < 0.9) $totalCwvIssues++;
                    }
                }

                // Total ranking keywords across all ranking sessions
                $totalRankingKw = 0;
                foreach ($group['ranking'] as $rr) {
                    $totalRankingKw += count($rr['keywords'] ?? []);
                }

                // Determine filter type for the row (used by the filter buttons)
                // If it has both, we mark it 'all'; individual filters show it if it matches any
                $rowTypes = [];
                if ($hasRanking) $rowTypes[] = 'ranking';
                if ($hasCwv)     $rowTypes[] = 'cwv';

                $latestDate = \Carbon\Carbon::parse($group['latest_at'])->format('d M Y');

                // JSON-safe key for JS lookup
                $jsKey = addslashes($key);
            @endphp

            <div class="result-row"
                 data-type="{{ implode(' ', $rowTypes) }}"
                 data-domain="{{ strtolower(parse_url($gUrl, PHP_URL_HOST) ?: $gUrl) }}">

                {{-- Domain + type pills --}}
                <div class="result-domain" style="min-width:220px; flex:1;">
                    <span style="font-weight:700;">{{ $gUrl }}</span>
                    @if($hasRanking)
                        <span class="tool-pill pill-ranking">Ranking</span>
                    @endif
                    @if($hasCwv)
                        <span class="tool-pill pill-cwv">Core Web Vitals</span>
                    @endif
                </div>

                {{-- Latest date --}}
                <div>
                    <span class="text-muted" style="font-size:.78rem;">{{ $latestDate }}</span>
                </div>

                {{-- View summary button → opens unified summary for this URL --}}
                <a class="view-summary-btn"
                    href="{{ route('seo.summary', [$created_by_user_id, $client_property_id, urlencode($key)]) }}">
                        View summary
                </a>
            </div>
            @empty
            <div class="empty-state">
                <i class="mdi mdi-chart-line-variant"></i>
                <p class="mb-0 fw-semibold">No saved analyses yet</p>
                <p class="small mt-1">Run a new analysis to see results here.</p>
                <button class="btn btn-sm btn-primary mt-2" onclick="showView('newAudit')">
                    Start new analysis
                </button>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         VIEW 3 — UNIFIED DOMAIN SUMMARY  (Tab-based layout)
    ═══════════════════════════════════════════════════════════ --}}
    <div id="viewSummary" style="display:none;" class="summary-pane">

        <button class="back-btn" onclick="showView('results')">
            <i class="mdi mdi-arrow-left"></i> Back to results
        </button>

        {{-- ── Domain header ── --}}
        <div class="summary-header-card mb-4">
            <div class="text-muted mb-1"
                 style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">
                DOMAIN SUMMARY
            </div>
            <div class="summary-domain-title" id="us_domain">—</div>
            <div class="summary-meta" id="us_meta">—</div>
        </div>

        {{-- ── Dynamic type-tab bar (built by JS based on available data) ── --}}
        <div class="summary-type-tabs" id="us_typeTabs"></div>

        {{-- ══ RANKING TAB PANE ══ --}}
        <div id="us_tabPane_ranking" style="display:none;">
            <div class="issues-card mb-3">
                <div class="issues-card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <i class="mdi mdi-chart-bar me-1 text-primary"></i>
                        Ranking &amp; Competitor Reports
                        <small>Select a date to view that session's keyword positions</small>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" id="us_rankingExportBtn">
                        <i class="mdi mdi-download me-1"></i> Export CSV
                    </button>
                </div>
                <div class="px-3 pt-3">
                    <ul class="nav nav-tabs" id="us_rankingDateTabs" role="tablist"></ul>
                </div>
                <div class="tab-content p-3" id="us_rankingDateContent"></div>
            </div>

            <div class="summary-stats-grid mt-3"
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
        </div>

        {{-- ══ CORE WEB VITALS TAB PANE ══ --}}
        <div id="us_tabPane_cwv" style="display:none;">
            <div class="issues-card mb-3">
                <div class="issues-card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <i class="mdi mdi-speedometer me-1 text-success"></i>
                        Core Web Vitals
                        <small>Select a date to compare scores over time</small>
                    </div>
                    <div class="d-flex gap-2" id="us_cwvStrategyTabs">
                        <button class="strategy-tab-btn active" data-us-strategy="mobile">
                            <i class="mdi mdi-cellphone me-1"></i> Mobile
                        </button>
                        <button class="strategy-tab-btn" data-us-strategy="desktop">
                            <i class="mdi mdi-monitor me-1"></i> Desktop
                        </button>
                    </div>
                </div>
                <div class="px-3 pt-3">
                    <ul class="nav nav-tabs" id="us_cwvDateTabs" role="tablist"></ul>
                </div>
                <div class="p-3">
                    <div id="us_cwvVitalsContent"></div>
                    <div class="d-flex gap-4 mt-4 flex-wrap">
                        <span><span class="legend-dot" style="background:#22c55e;"></span> Good</span>
                        <span><span class="legend-dot" style="background:#eab308;"></span> Needs Improvement</span>
                        <span><span class="legend-dot" style="background:#ef4444;"></span> Poor</span>
                    </div>
                </div>
            </div>

            <div class="issues-card">
                <div class="issues-card-header">
                    Most common issues
                    <small>Metrics below the recommended threshold for the selected session</small>
                </div>
                <table class="issues-table">
                    <thead>
                        <tr><th>Issue</th><th>Category</th><th>Value</th><th>Severity</th></tr>
                    </thead>
                    <tbody id="us_cwvIssuesBody"></tbody>
                </table>
            </div>
        </div>

        {{-- ══ REPORTING TAB PANE ══ --}}
        <div id="us_tabPane_reporting" style="display:none;">
            <div class="issues-card">
                <div class="issues-card-header">
                    <i class="mdi mdi-table-large me-1 text-warning"></i>
                    Reporting Sheet
                    <small>Saved reporting sessions for this domain</small>
                </div>
                <div id="us_reportingContent" class="p-3">
                    <div class="empty-state py-4">
                        <i class="mdi mdi-table-large"></i>
                        <p class="mb-0 fw-semibold">No reporting data saved for this URL</p>
                        <p class="small mt-1">Generate a reporting sheet from the New Analysis tab.</p>
                        <button class="btn btn-sm btn-primary mt-2"
                                onclick="showView('newAudit'); switchToolExternal('reporting')">
                            Generate Reporting Sheet
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="us_emptyState" style="display:none;" class="empty-state">
            <i class="mdi mdi-chart-line-variant"></i>
            <p class="mb-0 fw-semibold">No data for this URL yet</p>
        </div>

    </div>{{-- /viewSummary --}}

</div>{{-- /seo-wrap --}}
</div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
/* ══════════════════════════════════════════════════════════════════
   BLADE → JS DATA
   ══════════════════════════════════════════════════════════════════ */
const CSRF               = document.querySelector('input[name="_token"]').value;
const CREATED_BY_USER_ID = {{ $created_by_user_id }};
const CLIENT_PROPERTY_ID = {{ $client_property_id }};

const RANKING_FORM_URL  = '{{ route("ranking.competitor.report.form", [$created_by_user_id, $client_property_id]) }}';
const RANKING_SAVE_URL  = '{{ route("ranking.competitor.report.save") }}';
const CWV_FORM_URL      = '{{ route("core.web.vitals.form",           [$created_by_user_id, $client_property_id]) }}';
// const WA_FORM_URL      = '{{ route("website.health.audit", [$domain]) }}';
const REPORTING_FORM_URL= '{{ route("reporting.sheet.form",           [$created_by_user_id, $client_property_id]) }}';
let competitorAuditCache = {};
// All saved reports (keyed by id)
const SAVED_RANKING_MAP = {};
@foreach($savedRankingReports as $report)
SAVED_RANKING_MAP[{{ $report['id'] }}] = @json($report);
@endforeach

// All saved CWV (keyed by id)
const SAVED_CWV_MAP = {};
@foreach($savedCoreWebVitals as $rec)
SAVED_CWV_MAP[{{ $rec->id }}] = @json($rec->toArray());
@endforeach

// Grouped-by-URL map for the unified summary view
// Key = normalised URL key (same as PHP $key), value = { display_url, ranking:[], cwv:[] }
const GROUPED_URLS = @json($groupedUrls);

/* ══════════════════════════════════════════════════════════════════
   VIEW NAVIGATION
   ══════════════════════════════════════════════════════════════════ */
function showView(v) {
    ['newAudit','results','summary'].forEach(n => {
        document.getElementById('view'+n.charAt(0).toUpperCase()+n.slice(1)).style.display = 'none';
    });
    document.getElementById('view'+v.charAt(0).toUpperCase()+v.slice(1)).style.display = '';
    // Update nav
    document.getElementById('navNewAudit').classList.toggle('active', v === 'newAudit');
    document.getElementById('navResults').classList.toggle('active', v === 'results' || v === 'summary');

    // Update results count badge when switching to results
    if (v === 'results') {
        const rows = document.querySelectorAll('#savedResultsList .result-row');
        document.getElementById('resultsCount').textContent = rows.length + ' record' + (rows.length !== 1 ? 's' : '');
    }
}

/* ══════════════════════════════════════════════════════════════════
   TOOL SWITCHER
   ══════════════════════════════════════════════════════════════════ */
function switchTool(radio) {
    
    ['ranking','cwv','reporting','website_audit'].forEach(t => {
        document.getElementById('formRanking').style.display   = 'none';
        document.getElementById('formCWV').style.display       = 'none';
        document.getElementById('formReporting').style.display = 'none';
        document.getElementById('formWA').style.display = 'none';
        document.getElementById('lbl-ranking').classList.remove('selected');
        document.getElementById('lbl-cwv').classList.remove('selected');
        document.getElementById('lbl-reporting').classList.remove('selected');
        document.getElementById('lbl-website_audit').classList.remove('selected');
        hideLiveResults();
    });
    const map = { ranking: 'formRanking', cwv: 'formCWV', reporting: 'formReporting', website_audit: 'formWA' };
    document.getElementById(map[radio.value]).style.display = '';
    document.getElementById('lbl-' + radio.value).classList.add('selected');
}
function switchToolExternal(tool) {
    document.querySelector(`input[name="seoTool"][value="${tool}"]`).checked = true;
    document.querySelector(`input[name="seoTool"][value="${tool}"]`).dispatchEvent(new Event('change'));
}
function hideLiveResults() {
    ['r_resultsSection','c_resultsSection','p_resultsSection','wa_resultsSection','liveErrorSection'].forEach(id => {
        document.getElementById(id).style.display = 'none';
    });
}

/* ══════════════════════════════════════════════════════════════════
   RESULTS LIST FILTER / SEARCH
   ══════════════════════════════════════════════════════════════════ */
let activeFilter = 'all';
function setFilter(btn, filter) {
    activeFilter = filter;
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterResults();
}
function filterResults() {
    const q = document.getElementById('resultSearch').value.toLowerCase();
    document.querySelectorAll('#savedResultsList .result-row').forEach(row => {
        // data-type is now space-separated, e.g. "ranking cwv"
        const types       = (row.dataset.type || '').split(' ');
        const typeMatch   = activeFilter === 'all' || types.includes(activeFilter);
        const searchMatch = !q || row.dataset.domain.includes(q) || row.textContent.toLowerCase().includes(q);
        row.style.display = typeMatch && searchMatch ? '' : 'none';
    });
}

/* ══════════════════════════════════════════════════════════════════
   SUMMARY: CORE WEB VITALS
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
function cwvScoreClass(s)       { if(s===null||s===undefined) return ''; return s>=0.9?'good':s>=0.5?'average':'poor'; }
function cwvThresholdClass(v,g,p){ if(v===null||v===undefined) return 'average'; return v<=g?'good':v<=p?'average':'poor'; }
function cwvBadgeLabel(cls)     { return {good:'Good',average:'Needs Improvement',poor:'Poor'}[cls]??'—'; }

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

function flatRowToCWVData(row, strategy) {
    const KEYS = ['lcp','cls','tbt','inp','fcp','ttfb','si','tti'];
    const d = { performance_score: row[`${strategy}_performance_score`] ?? 0 };
    KEYS.forEach(m => {
        d[m] = { display: row[`${strategy}_${m}_display`]??'—', value: row[`${strategy}_${m}_value`]??null, score: row[`${strategy}_${m}_score`]??null };
    });
    return d;
}

let csSavedRow = null, csActiveStrategy = 'mobile';   // kept for compat if referenced elsewhere

/* ══════════════════════════════════════════════════════════════════
   UNIFIED SUMMARY  — viewUnifiedSummary(urlKey)
   Tab-based: Ranking | Core Web Vitals | Reporting
   Tabs are built dynamically — only tabs with data are shown.
   ══════════════════════════════════════════════════════════════════ */
const usDtInstances = {};
let   usActiveCwvRow      = null;
let   usActiveCwvStrategy = 'mobile';

function viewUnifiedSummary(urlKey) {
    const group = GROUPED_URLS[urlKey];
    if (!group) return;

    const hasRanking   = (group.ranking || []).length > 0;
    const hasCwv       = (group.cwv     || []).length > 0;
    const hasReporting = false;   // extend when saved reporting records are added

    // ── Domain header ────────────────────────────────────────────────
    const displayUrl = group.display_url ?? urlKey;
    const domainOnly = displayUrl.replace(/https?:\/\/(www\.)?/i, '').replace(/\/$/, '');
    document.getElementById('us_domain').textContent = domainOnly;
    document.getElementById('us_meta').textContent   =
        `${displayUrl}` +
        `${hasRanking  ? ' · ' + group.ranking.length + ' ranking session(s)' : ''}` +
        `${hasCwv      ? ' · ' + group.cwv.length     + ' CWV session(s)'     : ''}`;

    // ── Hide all panes + empty state ─────────────────────────────────
    ['ranking', 'cwv', 'reporting'].forEach(k =>
        document.getElementById(`us_tabPane_${k}`).style.display = 'none'
    );
    document.getElementById('us_emptyState').style.display = 'none';

    // ── Build dynamic tab bar ────────────────────────────────────────
    const tabDefs = [
        { key: 'ranking',   label: 'Ranking',          show: hasRanking },
        { key: 'cwv',       label: 'Core Web Vitals',  show: hasCwv },
        { key: 'reporting', label: 'Reporting',         show: hasReporting },
    ];
    const visibleTabs = tabDefs.filter(t => t.show);

    const tabsEl = document.getElementById('us_typeTabs');
    tabsEl.innerHTML = '';

    if (visibleTabs.length === 0) {
        document.getElementById('us_emptyState').style.display = '';
        showView('summary');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    visibleTabs.forEach((tab, i) => {
        const btn = document.createElement('button');
        btn.className        = 'summary-type-tab' + (i === 0 ? ' active' : '');
        btn.dataset.tab      = tab.key;
        btn.textContent      = tab.label;
        btn.addEventListener('click', function () {
            tabsEl.querySelectorAll('.summary-type-tab').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            usShowTab(this.dataset.tab);
        });
        tabsEl.appendChild(btn);
    });

    // ── RANKING: build date-session tabs ────────────────────────────
    if (hasRanking) {
        // Destroy stale DataTable instances
        Object.keys(usDtInstances).forEach(k => {
            if (usDtInstances[k]) { usDtInstances[k].destroy(); delete usDtInstances[k]; }
        });

        const dateTabs    = document.getElementById('us_rankingDateTabs');
        const dateContent = document.getElementById('us_rankingDateContent');
        dateTabs.innerHTML    = '';
        dateContent.innerHTML = '';

        group.ranking.forEach((report, idx) => {
            const isFirst   = idx === 0;
            const dateLabel = new Date(report.created_at)
                .toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
            const paneId    = `us_rPane_${idx}`;

            // Date tab button
            const li = document.createElement('li');
            li.className = 'nav-item';
            li.innerHTML = `<button class="nav-link ${isFirst ? 'active' : ''}"
                id="us_rTab_${idx}" data-bs-toggle="tab" data-bs-target="#${paneId}"
                type="button" role="tab" data-rs-idx="${idx}">
                ${dateLabel}
                <span class="badge bg-secondary ms-1">${(report.keywords || []).length} kw</span>
            </button>`;
            dateTabs.appendChild(li);

            // Tab pane wrapping per-keyword sub-tabs
            const kwTabsId    = `us_rKwTabs_${idx}`;
            const kwContentId = `us_rKwContent_${idx}`;
            const pane        = document.createElement('div');
            pane.className    = `tab-pane fade ${isFirst ? 'show active' : ''}`;
            pane.id           = paneId;
            pane.setAttribute('role', 'tabpanel');
            pane.innerHTML    = `
                <ul class="nav nav-tabs mb-3 mt-1" id="${kwTabsId}" role="tablist"></ul>
                <div class="tab-content" id="${kwContentId}"></div>`;
            dateContent.appendChild(pane);

            if (isFirst) {
                usLoadRankingSession(report, kwTabsId, kwContentId);
                usUpdateRankingStats(report);
            }
        });

        // Lazy-render keyword tabs when switching date sessions
        dateTabs.addEventListener('shown.bs.tab', function (e) {
            const idx    = parseInt(e.target.dataset.rsIdx);
            const report = group.ranking[idx];
            if (!report) return;
            const kwTabsId    = `us_rKwTabs_${idx}`;
            const kwContentId = `us_rKwContent_${idx}`;
            if (!document.getElementById(kwTabsId).children.length) {
                usLoadRankingSession(report, kwTabsId, kwContentId);
            }
            usUpdateRankingStats(report);
        });
    }

    // ── CWV: build date-session tabs ────────────────────────────────
    if (hasCwv) {
        usActiveCwvStrategy = 'mobile';
        const cwvDateTabs   = document.getElementById('us_cwvDateTabs');
        cwvDateTabs.innerHTML = '';

        group.cwv.forEach((row, idx) => {
            const isFirst   = idx === 0;
            const dateLabel = new Date(row.created_at)
                .toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
            const li = document.createElement('li');
            li.className = 'nav-item';
            li.innerHTML = `<button class="nav-link ${isFirst ? 'active' : ''}"
                data-us-cwv-idx="${idx}" type="button">
                ${dateLabel}
                <span class="badge ms-1" style="background:#dbeafe;color:#1e40af;font-size:.65rem;">
                    ${row.mobile_performance_score ?? '—'}
                </span>
            </button>`;
            li.querySelector('button').addEventListener('click', function () {
                cwvDateTabs.querySelectorAll('.nav-link').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                usActiveCwvRow = group.cwv[parseInt(this.dataset.usCwvIdx)];
                usRenderCWV();
            });
            cwvDateTabs.appendChild(li);
        });

        usActiveCwvRow = group.cwv[0];
        usRenderCWV();

        // Reset strategy toggle to Mobile
        document.getElementById('us_cwvStrategyTabs').querySelectorAll('.strategy-tab-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.usStrategy === 'mobile');
        });
    }

    // ── Show first tab ───────────────────────────────────────────────
    usShowTab(visibleTabs[0].key);

    showView('summary');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/** Show one tab pane, hide the rest */
function usShowTab(tabKey) {
    ['ranking', 'cwv', 'reporting'].forEach(k => {
        document.getElementById(`us_tabPane_${k}`).style.display = 'none';
    });
    const pane = document.getElementById(`us_tabPane_${tabKey}`);
    if (pane) pane.style.display = '';
}

function usLoadRankingSession(report, kwTabsId, kwContentId) {
    document.getElementById(kwTabsId).innerHTML    = '';
    document.getElementById(kwContentId).innerHTML = '';
    renderResults(
        report.results_json || [], report.location, [],
        report.client_domain || '', report.competitor_option || '',
        kwTabsId, kwContentId, usDtInstances
    );
}

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

function usRenderCWV() {
    if (!usActiveCwvRow) return;
    const row      = usActiveCwvRow;
    const strategy = usActiveCwvStrategy;
    document.getElementById('us_cwvVitalsContent').innerHTML =
        buildCWVHtml(flatRowToCWVData(row, strategy), strategy);

    // Issues table
    const tbody = document.getElementById('us_cwvIssuesBody');
    tbody.innerHTML = '';
    CWV_METRICS.forEach(m => {
        const score = row[`${strategy}_${m.key}_score`];
        if (score === null || score === undefined || score >= 0.9) return;
        const cls    = score >= 0.5 ? 'average' : 'poor';
        const disp   = row[`${strategy}_${m.key}_display`] ?? '—';
        const sevCls = cls === 'poor' ? 'sev-critical' : 'sev-high';
        const sevTxt = cls === 'poor' ? 'High' : 'Medium';
        tbody.innerHTML += `<tr>
            <td>${m.label}</td>
            <td><span class="text-muted">${m.abbr}</span></td>
            <td><strong>${disp}</strong></td>
            <td><span class="${sevCls}">${sevTxt}</span></td>
        </tr>`;
    });
    if (!tbody.innerHTML) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No issues detected 🎉</td></tr>';
    }
}

// CWV strategy toggle (mobile / desktop)
document.getElementById('us_cwvStrategyTabs').addEventListener('click', function(e) {
    const btn = e.target.closest('.strategy-tab-btn');
    if (!btn) return;
    this.querySelectorAll('.strategy-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    usActiveCwvStrategy = btn.dataset.usStrategy;
    usRenderCWV();
});

// Ranking CSV export — exports the active keyword pane of the active date session
document.getElementById('us_rankingExportBtn').addEventListener('click', function() {
    // Find the active date pane
    const activePane = document.querySelector('#us_rankingDateContent .tab-pane.active');
    if (!activePane) return;
    const contentId = activePane.querySelector('[id^="us_rKwContent_"]')?.id;
    if (contentId) exportActivePaneCsv(contentId);
});

// ── Keep old snippet-modal listener working for the new content containers ──
document.getElementById('us_rankingDateContent').addEventListener('click', function(e) {
    const cell = e.target.closest('.snippet-cell');
    if (!cell) return;
    document.getElementById('snippetModalTitle').textContent = cell.dataset.title||'—';
    document.getElementById('snippetModalUrl').innerHTML = `<a href="${cell.dataset.url}" target="_blank" class="small">${cell.dataset.url}</a>`;
    document.getElementById('snippetModalBody').textContent = cell.dataset.snippet||'No description available.';
    new bootstrap.Modal(document.getElementById('snippetModal')).show();
});

/* ══════════════════════════════════════════════════════════════════
   FORM: RANKING — Submit
   ══════════════════════════════════════════════════════════════════ */
const dtInstances = {};
function destroyAllTables(registry) {
    Object.keys(registry).forEach(k => { if(registry[k]){registry[k].destroy(); delete registry[k];} });
}

document.getElementById('rankingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const location    = document.getElementById('r_locationSelect').value;
    const keywords    = document.getElementById('r_keywordsInput').value.trim();
    const competitor  = document.querySelector('input[name="competitorOption"]:checked')?.value || '';
    const compUrls    = document.getElementById('r_competitorUrls').value.trim();
    const ourClient   = document.getElementById('r_ourclientInput').value.trim();

    let valid = true;
    if (!location) { document.getElementById('r_locationSelect').classList.add('is-invalid'); valid=false; }
    else             document.getElementById('r_locationSelect').classList.remove('is-invalid');
    if (!keywords)  { document.getElementById('r_keywordsInput').classList.add('is-invalid'); valid=false; }
    else             document.getElementById('r_keywordsInput').classList.remove('is-invalid');
    if (!valid) return;

    rSetLoading(true);
    document.getElementById('liveErrorSection').style.display = 'none';
    document.getElementById('r_resultsSection').style.display = 'none';
    document.getElementById('r_saveStatus').className = 'save-status d-none';
    destroyAllTables(dtInstances);

    const keywordList    = keywords.split(',').map(k=>k.trim()).filter(Boolean);
    const competitorList = compUrls ? compUrls.split(',').map(u=>u.trim()).filter(Boolean) : [];

    try {
        const promises = keywordList.map(kw =>
            fetch(RANKING_FORM_URL, {
                method:'POST',
                headers:{ 'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json' },
                body: JSON.stringify({ keyword:kw, location, competitor_option:competitor, competitor_urls:competitorList })
            }).then(r=>r.json()).then(data=>({keyword:kw, data}))
        );
        const results = await Promise.all(promises);
        renderResults(results, location, competitorList, ourClient, competitor,
                      'r_keywordTabs', 'r_keywordTabsContent', dtInstances);
        document.getElementById('r_resultsSubtitle').textContent = `Location: ${location} · ${results.length} keyword(s) analyzed`;
        document.getElementById('r_resultsSection').style.display = 'block';
        document.getElementById('r_resultsSection').scrollIntoView({ behavior:'smooth' });
        await saveRankingReport(results, location, keywordList, ourClient, competitor);
    } catch(err) {
        showLiveError('Failed to fetch results: ' + err.message);
    } finally {
        rSetLoading(false);
    }
});

async function saveRankingReport(results, location, keywordList, clientDomain, competitorOption) {
    const el = document.getElementById('r_saveStatus');
    try {
        el.className = 'save-status text-muted';
        el.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving…';
        const resp = await fetch(RANKING_SAVE_URL, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({ created_by_user_id: CREATED_BY_USER_ID, client_property_id: CLIENT_PROPERTY_ID, location, keywords: keywordList, client_domain: clientDomain, competitor_option: competitorOption||null, results_json: results })
        });
        const json = await resp.json();
        if (json.success) {
            el.className = 'save-status text-success';
            el.innerHTML = '<i class="mdi mdi-check-circle me-1"></i> Report saved.';
            // Inject into saved map + add to results list
            SAVED_RANKING_MAP[json.report.id] = json.report;
            prependRankingResultRow(json.report);
        } else { throw new Error(json.message||'Save failed'); }
    } catch(err) {
        el.className = 'save-status text-danger';
        el.innerHTML = '<i class="mdi mdi-alert-circle me-1"></i> Could not save: ' + err.message;
    }
}

function prependRankingResultRow(report) {
    const list = document.getElementById('savedResultsList');
    const empty = list.querySelector('.empty-state');
    if (empty) empty.remove();

    // ── Update GROUPED_URLS so viewUnifiedSummary works immediately ──
    const rawUrl  = report.client_domain || '';
    const normKey = rawUrl.toLowerCase()
        .replace(/https?:\/\/(www\.)?/i,'')
        .replace(/\/$/,'');

    if (!GROUPED_URLS[normKey]) {
        GROUPED_URLS[normKey] = {
            display_url: rawUrl,
            ranking: [],
            cwv: [],
            latest_at: new Date().toISOString(),
        };
    }
    GROUPED_URLS[normKey].ranking.unshift(report);   // newest first

    // ── Check if a row for this URL already exists — if so, just update it ──
    const existing = list.querySelector(`.result-row[data-url-key="${CSS.escape(normKey)}"]`);
    if (existing) {
        // Add Ranking pill if not already there
        if (!existing.querySelector('.pill-ranking')) {
            existing.querySelector('.result-domain').insertAdjacentHTML('beforeend',
                '<span class="tool-pill pill-ranking">Ranking</span>');
        }
        existing.dataset.type = (existing.dataset.type + ' ranking').trim();
        return;
    }

    // ── Create a fresh row ──────────────────────────────────────────
    const kCount = (report.keywords||[]).length;
    const row    = document.createElement('div');
    row.className          = 'result-row';
    row.dataset.type       = 'ranking';
    row.dataset.domain     = rawUrl.toLowerCase().replace(/https?:\/\/(www\.)?/,'').split('/')[0];
    row.dataset.urlKey     = normKey;
    row.innerHTML = `
        <div class="result-domain" style="min-width:220px;flex:1;">
            <span style="font-weight:700;">${escapeHtml(rawUrl||'—')}</span>
            <span class="tool-pill pill-ranking">Ranking</span>
        </div>
        <div style="min-width:110px;"></div>
        <div style="min-width:120px;">
            <span class="issue-pill pill-high">${kCount} keywords</span>
        </div>
        <div><span class="text-muted" style="font-size:.78rem;">${new Date().toLocaleDateString('en-IN',{day:'2-digit',month:'short',year:'numeric'})}</span></div>
        <a class="view-summary-btn" href="/seo-summary/${CREATED_BY_USER_ID}/${CLIENT_PROPERTY_ID}/${encodeURIComponent(normKey)}">View summary</a>`;
    list.insertBefore(row, list.firstChild);
}

document.getElementById('r_resetBtn').addEventListener('click', function() {
    document.getElementById('r_competitorDomainsInput').style.display = 'none';
    document.getElementById('r_resultsSection').style.display = 'none';
    document.getElementById('liveErrorSection').style.display = 'none';
    document.getElementById('r_keywordTabs').innerHTML = '';
    document.getElementById('r_keywordTabsContent').innerHTML = '';
    document.getElementById('r_saveStatus').className = 'save-status d-none';
    destroyAllTables(dtInstances);
});
document.getElementById('r_defineCompetitor').addEventListener('change', function() {
    document.getElementById('r_competitorDomainsInput').style.display = this.checked ? 'block' : 'none';
});
document.getElementById('r_autoCheckCompetitor').addEventListener('change', function() {
    if(this.checked) document.getElementById('r_competitorDomainsInput').style.display = 'none';
});
function rSetLoading(on) {
    document.getElementById('r_generateBtn').disabled = on;
    document.getElementById('r_generateBtnSpinner').classList.toggle('d-none', !on);
    document.getElementById('r_generateBtnText').textContent = on ? 'Generating…' : 'Generate Report';
}

/* ══════════════════════════════════════════════════════════════════
   FORM: CORE WEB VITALS — Submit
   ══════════════════════════════════════════════════════════════════ */
let cwvAllData = {}, cwvActiveStrategy = 'mobile';

document.getElementById('cwvForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const input = document.getElementById('c_ourclientInput');
    if (!input.value.trim()) { input.classList.add('is-invalid'); return; }
    input.classList.remove('is-invalid');
    cSetLoading(true);
    document.getElementById('liveErrorSection').style.display = 'none';
    document.getElementById('c_resultsSection').style.display = 'none';
    try {
        const resp = await fetch(CWV_FORM_URL, {
            method:'POST',
            headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: new FormData(this)
        });
        const json = await resp.json();
        if (!resp.ok || json.error) { showLiveError(json.error??'An unexpected error occurred.'); return; }
        cwvAllData = json.data;
        document.getElementById('c_resultUrl').textContent = json.url;
        cwvActiveStrategy = 'mobile';
        document.querySelectorAll('#c_strategyTabs .strategy-tab-btn').forEach(b => {
            b.classList.toggle('active', b.dataset.strategy==='mobile');
        });
        document.getElementById('c_vitalsContent').innerHTML = buildCWVHtml(cwvAllData['mobile'], 'mobile');
        document.getElementById('c_resultsSection').style.display = 'block';
        document.getElementById('c_resultsSection').scrollIntoView({ behavior:'smooth' });
    } catch(err) {
        showLiveError('Network error: ' + (err.message ?? JSON.stringify(err)));
    } finally {
        cSetLoading(false);
    }
});
document.getElementById('c_strategyTabs').addEventListener('click', function(e) {
    const btn = e.target.closest('.strategy-tab-btn');
    if (!btn) return;
    this.querySelectorAll('.strategy-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    cwvActiveStrategy = btn.dataset.strategy;
    if (cwvAllData[cwvActiveStrategy]) {
        document.getElementById('c_vitalsContent').innerHTML = buildCWVHtml(cwvAllData[cwvActiveStrategy], cwvActiveStrategy);
    }
});
document.getElementById('c_resetBtn').addEventListener('click', function() {
    document.getElementById('c_resultsSection').style.display = 'none';
    document.getElementById('liveErrorSection').style.display = 'none';
    document.getElementById('c_vitalsContent').innerHTML = '';
    cwvAllData = {};
});
function cSetLoading(on) {
    document.getElementById('c_generateBtn').disabled = on;
    document.getElementById('c_generateBtnSpinner').classList.toggle('d-none', !on);
    document.getElementById('c_generateBtnText').textContent = on ? 'Analysing…' : 'Analyse Web Vitals';
}
let waAllData = {};

document.getElementById('waForm').addEventListener('submit', async function(e) {
    
    e.preventDefault();
    const input = document.getElementById('wa_ourclientInput');
    if (!input.value.trim()) { 
        input.classList.add('is-invalid'); 
        return; 
    }
    input.classList.remove('is-invalid');
    waSetLoading(true);
    document.getElementById('liveErrorSection').style.display = 'none';
    document.getElementById('wa_resultsSection').style.display = 'none';
    let domain = input.value.trim();
    domain = domain.replace(/^https?:\/\//i, '').replace(/\/$/, '');
    // input.value = domain;
    console.log(domain);
    // let domain = input.value.trim();
    
    try {
        const resp = await fetch('/website-health-audit/' + encodeURIComponent(domain), {
            method:'POST',
            headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({ domain: domain })
        });
        const json = await resp.json();
        
        if (!resp.ok || !json.success) {
            showLiveError(json.message || 'An unexpected error occurred.');
            return;
        }
        
        waAllData = json;
        renderWebsiteAuditResults(json);
        document.getElementById('wa_resultsSection').style.display = 'block';
        document.getElementById('wa_resultsSection').scrollIntoView({ behavior: 'smooth' });
    } catch(err) {
        console.error('Fetch error:', err);
        showLiveError('Network error: ' + err.message);
    } finally {
        waSetLoading(false);
    }
});

/* ══════════════════════════════════════════════════════════════════
   FORM: REPORTING SHEET — Submit
   ══════════════════════════════════════════════════════════════════ */
window._xmlFiles = [];

document.getElementById('reportingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!this.checkValidity()) { $(this).addClass('was-validated'); return; }
    pSetLoading(true);
    document.getElementById('liveErrorSection').style.display = 'none';
    const fd = new FormData(this);
    if (window._xmlFiles && window._xmlFiles.length) {
        fd.delete('xml_files[]');
        window._xmlFiles.forEach(f => fd.append('xml_files[]', f));
    }
    $.ajax({
        url: REPORTING_FORM_URL, method:'POST', data:fd, processData:false, contentType:false,
        success: function(res) {
            pSetLoading(false);
            if (res.success && res.rows) {
                renderReportingDashboard(res.rows, res.spreadsheet_url??null);
            } else {
                showLiveError(res.message??'Unexpected response from server.');
            }
        },
        error: function(xhr) {
            pSetLoading(false);
            showLiveError(xhr.responseJSON?.message??'Server error. Please try again.');
        }
    });
});
function renderReportingDashboard(rows, spreadsheetUrl) {
    const body = document.getElementById('p_dashboardBody');
    body.innerHTML = '';
    rows.forEach(function(row, idx) {
        const docCell = row.url
            ? `<td><a href="${row.url}" target="_blank">${row.url}</a></td>`
            : `<td>${row.note||'—'}</td>`;
        body.innerHTML += `<tr><td>${idx+1}</td><td>${row.name||'—'}</td>${docCell}</tr>`;
    });
    if (spreadsheetUrl) {
        document.getElementById('p_spreadsheetLink').href = spreadsheetUrl;
        document.getElementById('p_spreadsheetLink').classList.remove('d-none');
    }
    document.getElementById('p_resultsSection').style.display = 'block';
    document.getElementById('p_resultsSection').scrollIntoView({ behavior:'smooth' });
}
document.getElementById('p_resetBtn').addEventListener('click', function() {
    document.getElementById('p_resultsSection').style.display = 'none';
    document.getElementById('liveErrorSection').style.display = 'none';
    document.getElementById('p_spreadsheetLink').classList.add('d-none');
    document.getElementById('p_dashboardBody').innerHTML = '';
    window._xmlFiles = [];
    document.getElementById('p_xmlFileList').innerHTML = '';
    document.getElementById('p_xmlFilesInput').value = '';
    document.getElementById('p_xmlDropZone').style.background = '';
});
function pSetLoading(on) {
    document.getElementById('p_generateBtn').disabled = on;
    document.getElementById('p_generateBtnSpinner').classList.toggle('d-none', !on);
    document.getElementById('p_generateBtnText').textContent = on ? 'Generating…' : 'Generate Reporting Sheet';
}
// XML browse
$('#p_xmlFilesInput').on('change', function() {
    Array.from(this.files).forEach(f => {
        if (!window._xmlFiles.find(x=>x.name===f.name&&x.size===f.size)) window._xmlFiles.push(f);
    });
    renderXmlChips();
});
function handleXmlDrop(event) {
    event.preventDefault();
    document.getElementById('p_xmlDropZone').style.background = '';
    Array.from(event.dataTransfer.files)
        .filter(f=>f.name.endsWith('.xml')||f.type==='application/xml'||f.type==='text/xml')
        .forEach(f=>{ if(!window._xmlFiles.find(x=>x.name===f.name&&x.size===f.size)) window._xmlFiles.push(f); });
    renderXmlChips();
}
function renderXmlChips() {
    const list = document.getElementById('p_xmlFileList');
    list.innerHTML = '';
    window._xmlFiles.forEach(function(file, idx) {
        const span = document.createElement('span');
        span.className = 'badge bg-light text-dark border d-flex align-items-center gap-1 py-1 px-2';
        span.style.fontSize = '.8rem';
        span.innerHTML = `<i class="mdi mdi-file-xml-box text-primary"></i>${file.name}<button type="button" class="btn-close btn-close-sm ms-1" style="font-size:.6rem;"></button>`;
        span.querySelector('button').addEventListener('click', function() {
            window._xmlFiles.splice(idx,1); renderXmlChips();
        });
        list.appendChild(span);
    });
}
/* ══════════════════════════════════════════════════════════════════
   SHARED: RENDER RANKING RESULTS (live + saved)
   ══════════════════════════════════════════════════════════════════ */
function renderResults(results, location, competitorList, clientDomain, competitorMode,
                       tabsElId, contentElId, dtRegistry) {
    const tabsEl    = document.getElementById(tabsElId);
    const contentEl = document.getElementById(contentElId);

    results.forEach((res, idx) => {
        const safeName  = `${tabsElId}_kw_${idx}`;
        const isFirst   = idx === 0;
        const aioData   = res.data?.ai_overview ?? null;

        // Collect all AIO source links for cross-referencing
        const aioSources    = collectAioSources(aioData);
        const aioSourceLinks = aioSources.map(s => s.link).filter(Boolean);

        // ── Tab pill — blue dot when AIO is present ────────────────
        const aioPresent = aioSources.length > 0
            || !!(aioData?.text)
            || !!(aioData?.text_blocks?.length);
        const aioTabDot  = aioPresent
            ? `<span title="AI Overview detected" style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#1a73e8;margin-left:5px;vertical-align:middle;flex-shrink:0;"></span>`
            : '';

        const li = document.createElement('li');
        li.className = 'nav-item';
        li.innerHTML = `<button class="nav-link ${isFirst ? 'active' : ''}" id="tab-${safeName}"
            data-bs-toggle="tab" data-bs-target="#pane-${safeName}" type="button" role="tab">
            ${escapeHtml(res.keyword)}
            <span class="badge bg-secondary ms-1">${(res.data?.organic_results || []).length}</span>
            ${aioTabDot}
        </button>`;
        tabsEl.appendChild(li);

        // ── Classify organic rows ──────────────────────────────────
        const allRows   = res.data?.organic_results || [];
        const clientRow = allRows.find(r => domainMatch(r.link, clientDomain));
        const clientPos = clientRow?.position ?? Infinity;

        let competitorRows;
        if (competitorMode === 'auto')
            competitorRows = allRows.filter(r => r.position < clientPos && !domainMatch(r.link, clientDomain));
        else if (competitorMode === 'define')
            competitorRows = allRows.filter(r => competitorList.some(c => domainMatch(r.link, c)));
        else
            competitorRows = [];

        const orgTableId  = `dt-org-${safeName}`;
        const compTableId = `dt-comp-${safeName}`;

        // ── Build pane ─────────────────────────────────────────────
        const pane = document.createElement('div');
        pane.className = `tab-pane fade ${isFirst ? 'show active' : ''}`;
        pane.id        = `pane-${safeName}`;
        pane.setAttribute('role', 'tabpanel');

        pane.innerHTML = `
            ${buildAiOverview(aioData, aioSources, clientDomain, competitorRows)}

            <h6 class="fw-semibold mb-2 mt-1">
                <i class="mdi mdi-magnify me-1 text-primary"></i>
                Organic Results <span class="badge bg-secondary ms-1">${allRows.length}</span>
            </h6>
            <p class="small text-muted mb-2">Click a snippet to view the full description.</p>
            <div class="table-responsive mb-4">
                <table id="${orgTableId}" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr><th>Rank</th><th>Title</th><th>URL</th><th>Domain</th><th>Snippet</th><th>Role</th></tr>
                    </thead>
                    <tbody>${buildOrganic(allRows, competitorRows, clientDomain, aioSourceLinks)}</tbody>
                </table>
            </div>

            <h6 class="fw-semibold mb-2">
                <i class="mdi mdi-chart-bar me-1 text-warning"></i>
                Competitor Analysis <span class="badge bg-secondary ms-1">${competitorRows.length}</span>
            </h6>
            <div class="table-responsive">
                <table id="${compTableId}" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr><th>Position</th><th>Title</th><th>URL</th><th>Domain</th><th>AI Overview</th><th>Role</th></tr>
                    </thead>
                    <tbody>${buildCompetitor(competitorRows, aioSourceLinks)}</tbody>
                </table>
            </div>`;

        contentEl.appendChild(pane);

        // setTimeout(() => {
        //     dtRegistry[orgTableId]  = $(`#${orgTableId}`).DataTable({
        //         pageLength: 25, order: [[0, 'asc']],
        //         columnDefs: [{ orderable: false, targets: [4, 5] }],
        //         language: { search: 'Filter:' },
        //         dom: '<"d-flex justify-content-between align-items-center mb-2"lf>rtip'
        //     });
        //     dtRegistry[compTableId] = $(`#${compTableId}`).DataTable({
        //         pageLength: 25, order: [[0, 'asc']],
        //         columnDefs: [{ orderable: false, targets: [4, 5] }],
        //         language: { search: 'Filter:' },
        //         dom: '<"d-flex justify-content-between align-items-center mb-2"lf>rtip'
        //     });
        // }, 100);
    });
}
/* ══════════════════════════════════════════════════════════════════
   COMPETITOR AUDIT BUTTON HANDLERS
   ══════════════════════════════════════════════════════════════════ */
function initCompetitorAuditButtons() {
    // Use event delegation since tables are dynamically loaded
    document.body.addEventListener('click', async function(e) {
        const auditBtn = e.target.closest('.audit-competitor-btn');
        if (!auditBtn) return;
        
        e.preventDefault();
        const url = auditBtn.dataset.url;
        const title = auditBtn.dataset.title;
        
        if (url) {
            await openCompetitorAuditModal(url, title);
        }
    });
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initCompetitorAuditButtons();
});

/* ══════════════════════════════════════════════════════════════════
   HELPER — flatten all AIO sources regardless of SerpAPI shape
   ══════════════════════════════════════════════════════════════════ */
function collectAioSources(aioData) {
    if (!aioData) return [];

    /* ── New SerpAPI shape: reference_links ───────────────────── */
    if (Array.isArray(aioData.reference_links) && aioData.reference_links.length) {
        const seen = new Set();
        return aioData.reference_links
            .filter(s => {
                if (!s.link || seen.has(s.link)) return false;
                seen.add(s.link); return true;
            })
            .map(s => ({
                index:   s.index  ?? null,
                title:   s.title  || null,
                link:    s.link,
                snippet: s.snippet || null,
                domain:  s.source  || extractDomain(s.link),
                favicon: s.favicon || null,
            }));
    }

    /* ── Legacy shape fallback (sources / blocks[].sources) ────── */
    let sources = [];
    if (Array.isArray(aioData.sources)) sources = aioData.sources;
    if (Array.isArray(aioData.blocks))
        aioData.blocks.forEach(b => { if (Array.isArray(b.sources)) sources = sources.concat(b.sources); });
    const seen = new Set();
    return sources.filter(s => {
        if (!s.link || seen.has(s.link)) return false;
        seen.add(s.link); return true;
    }).map(s => ({ ...s, domain: s.domain || extractDomain(s.link) }));
}

/* ══════════════════════════════════════════════════════════════════
   BUILD — AI Overview Panel
   ══════════════════════════════════════════════════════════════════ */
function buildAiOverview(aioData, aioSources, clientDomain, competitorRows) {

    /* ── AIO absent ───────────────────────────────────────────── */
    if (!aioData) {
        return `
        <div class="aio-wrap mb-4">
            <div class="aio-header">
                <span class="aio-google-pill"><i class="mdi mdi-robot-outline me-1"></i>AI Overview</span>
                <span class="aio-absent-pill"><i class="mdi mdi-minus-circle-outline me-1"></i>Not triggered for this keyword</span>
            </div>
        </div>`;
    }
    

    /* ── Classify sources ─────────────────────────────────────── */
    let clientFound      = false;
    let competitorCount  = 0;
    const classifiedSources = aioSources.map(src => {
        const domain   = src.domain || extractDomain(src.link || '');
        const isClient = domainMatch(src.link || '', clientDomain);
        const isComp   = !isClient && competitorRows.some(c => domainMatch(c.link, src.link || ''));
        if (isClient) clientFound    = true;
        if (isComp)   competitorCount++;
        return { ...src, domain, isClient, isComp };
    });
    

    /* ── Header status chips ──────────────────────────────────── */
    const clientChip = clientFound
        ? `<span class="aio-client-pill"><i class="mdi mdi-check-circle me-1"></i>Our client featured</span>`
        : `<span class="aio-noclient-pill"><i class="mdi mdi-close-circle me-1"></i>Our client not featured</span>`;
    const compChip = competitorCount > 0
        ? `<span class="aio-comp-pill"><i class="mdi mdi-alert me-1"></i>${competitorCount} competitor${competitorCount > 1 ? 's' : ''} in AI Overview</span>`
        : '';
    console.log('Data:', clientChip, compChip);

    /* ── AI Answer text ───────────────────────────────────────── */
    let answerHtml = '';
    if (Array.isArray(aioData.text_blocks) && aioData.text_blocks.length) {

        // Build a reference index → link map for inline footnote anchors
        const refMap = {};
        (aioData.reference_links || []).forEach(r => { if (r.index != null) refMap[r.index] = r; });

        let innerHtml = '';
        aioData.text_blocks.forEach(block => {
            if (block.type === 'paragraph' && block.answer) {
                innerHtml += `<p style="margin:0 0 7px;line-height:1.65;">${escapeHtml(block.answer)}`;
                // Inline reference chips
                if (Array.isArray(block.reference_indexes)) {
                    block.reference_indexes.forEach(i => {
                        const ref = refMap[i];
                        if (ref) {
                            innerHtml += ` <a href="${escapeHtml(ref.link)}" target="_blank" rel="noopener"
                                title="${escapeHtml(ref.title || ref.source || '')}"
                                style="display:inline-flex;align-items:center;gap:3px;background:#e8f0fe;color:#1a73e8;
                                    border-radius:4px;padding:0 6px;font-size:.68rem;font-weight:600;
                                    text-decoration:none;vertical-align:middle;margin-left:2px;">[${i}]</a>`;
                        }
                    });
                }
                innerHtml += `</p>`;

            } else if (block.type === 'unordered_list' && Array.isArray(block.items)) {
                innerHtml += `<ul style="margin:0 0 10px;padding-left:20px;">`;
                block.items.forEach(item => {
                    if (!item.answer) return;
                    innerHtml += `<li style="margin-bottom:4px;line-height:1.6;">${escapeHtml(item.answer)}</li>`;
                });
                innerHtml += `</ul>`;
            }
        });

        if (innerHtml) {
            answerHtml = `
            <div class="aio-answer-box">
                <strong><i class="mdi mdi-text-box-outline me-1"></i>AI-generated answer</strong>
                <div id="aio-text-short">${innerHtml}</div>
            </div>`;
        }

    /* ── Legacy fallback: aioData.text (plain string) ─────────── */
    } else if (aioData.text && aioData.text.trim()) {
        const preview = escapeHtml(aioData.text.trim().substring(0, 600));
        const full    = escapeHtml(aioData.text.trim());
        const hasMore = aioData.text.trim().length > 600;
        answerHtml = `
        <div class="aio-answer-box">
            <strong><i class="mdi mdi-text-box-outline me-1"></i>AI-generated answer</strong>
            <span id="aio-text-short">${preview}${hasMore ? '…' : ''}</span>
            ${hasMore ? `<span id="aio-text-full" style="display:none;">${full}</span>
            <a href="#" class="small ms-1" style="color:#1a73e8;text-decoration:none;"
            onclick="event.preventDefault();document.getElementById('aio-text-short').style.display='none';
                        document.getElementById('aio-text-full').style.display='inline';this.style.display='none';">
            Show more</a>` : ''}
        </div>`;
    }

    /* ── Blocks (legacy shape only) ────────────────────────────── */
    let blocksHtml = '';
    if (!Array.isArray(aioData.text_blocks) && Array.isArray(aioData.blocks) && aioData.blocks.length) {
        let inner = '';
        aioData.blocks.forEach(block => {
            if (block.type === 'paragraph' && block.text)
                inner += `<p class="small text-muted mb-2">${escapeHtml(block.text)}</p>`;
            else if (block.type === 'list' && Array.isArray(block.list)) {
                inner += `<ul class="small text-muted mb-2 ps-3">`;
                block.list.forEach(item => { inner += `<li>${escapeHtml(item)}</li>`; });
                inner += `</ul>`;
            }
        });
        if (inner) blocksHtml = `
        <div class="aio-answer-box mb-3">
            <strong><i class="mdi mdi-format-list-text me-1"></i>AI Overview content</strong>
            ${inner}
        </div>`;
    }

    /* ── Sources list ─────────────────────────────────────────── */
    let sourcesHtml = '';
    if (classifiedSources.length) {
        const rows = classifiedSources.map(src => {
            const favicon  = '';
            const rowCls   = src.isClient ? 'aio-is-client' : src.isComp ? 'aio-is-competitor' : 'aio-is-low-competitor';
            const badge    = src.isClient
                ? `<span class="aio-role-badge aio-role-client">Our Client</span>`
                : src.isComp
                    ? `<span class="aio-role-badge aio-role-comp">Competitor</span>`
                    : '';
            return `
            <div class="aio-source-row ${rowCls}">
                <img class="aio-source-favicon" src="${favicon}" alt="" loading="lazy" onerror="this.style.display='none'">
                <a href="${escapeHtml(src.link || '#')}" target="_blank" rel="noopener"
                   class="aio-source-title text-decoration-none text-dark"
                   title="${escapeHtml(src.link || '')}">${escapeHtml(src.title || src.domain || '—')}</a>
                <span class="aio-source-domain">${escapeHtml(src.domain)}</span>
                ${badge}
            </div>`;
        }).join('');

        sourcesHtml = `
        <div class="aio-sources-label">
            <i class="mdi mdi-link-variant me-1"></i>Sources cited (${classifiedSources.length})
        </div>
        ${rows}`;
    } else if (aioData) {
        sourcesHtml = `<p class="text-muted small mb-0"><i class="mdi mdi-information-outline me-1"></i>AI Overview present but no source links were returned.</p>`;
    }

    return `
    <div class="aio-wrap mb-4">
        <div class="aio-header">
            <span class="aio-google-pill"><i class="mdi mdi-robot-outline me-1"></i>AI Overview</span>
            <span class="aio-detected-pill"><i class="mdi mdi-check-circle me-1"></i>Detected</span>
            ${clientChip}
            ${compChip}
        </div>
        <div class="aio-body">
            ${answerHtml}
            ${blocksHtml}
            ${sourcesHtml}
            ${compSpotlightHtml}
        </div>
    </div>`;
}

/* ── Competitor spotlight (below sources) ─────────────────── */
let compSpotlightHtml = '';
const compSources = classifiedSources.filter(s => s.isComp);
if (compSources.length > 0) {
    const compItems = compSources.map(src => {
        const favicon = src.favicon || `https://www.google.com/s2/favicons?sz=16&domain_url=${encodeURIComponent(src.domain)}`;
        return `
        <div class="aio-comp-cited-item">
            <img src="${favicon}" alt="" loading="lazy"
                 style="width:16px;height:16px;border-radius:2px;margin-top:3px;flex-shrink:0;"
                 onerror="this.style.display='none'">
            <div class="aio-comp-cited-body">
                <div class="aio-comp-cited-title" title="${escapeHtml(src.title || src.domain)}">
                    ${escapeHtml(src.title || src.domain || '—')}
                </div>
                <div class="aio-comp-cited-href">
                    <a href="${escapeHtml(src.link || '#')}" target="_blank" rel="noopener"
                       style="color:#6b7280;text-decoration:none;">${escapeHtml(src.link || '—')}</a>
                </div>
            </div>
            <span class="aio-comp-cited-domain-badge">${escapeHtml(src.domain)}</span>
        </div>`;
    }).join('');

    compSpotlightHtml = `
    <div class="aio-comp-spotlight">
        <div class="aio-comp-spotlight-header">
            <i class="mdi mdi-alert-circle"></i>
            ${compSources.length} Competitor Page${compSources.length > 1 ? 's' : ''} Cited by AI Overview
        </div>
        ${compItems}
        <div class="aio-comp-spotlight-tip">
            <i class="mdi mdi-lightbulb-on-outline" style="flex-shrink:0;margin-top:1px;"></i>
            Google's AI is pulling these competitor pages to answer this query.
            Target the same topics with stronger, more comprehensive content to compete for AI visibility.
        </div>
    </div>`;
}

/* ══════════════════════════════════════════════════════════════════
   BUILD — Organic table rows
   aioSourceLinks: string[] of URLs cited in AI Overview
   ══════════════════════════════════════════════════════════════════ */
function buildOrganic(rows, competitorRows, clientDomain, aioSourceLinks = []) {
    return rows.map(r => {
        const isClient  = domainMatch(r.link, clientDomain);
        const isComp    = competitorRows.some(c => c.link === r.link);
        const inAio     = aioSourceLinks.some(aioLink => domainMatch(r.link, aioLink));

        // Row class: AIO highlight takes priority, then client green
        let rowCls = '';
        if (isClient && inAio) rowCls = 'aio-client-in-overview';
        else if (isClient)     rowCls = 'table-success';
        else if (inAio)        rowCls = 'aio-in-overview';

        const aioTag = inAio
            ? `<span class="aio-overview-tag" title="Appears in AI Overview">AI Overview</span>`
            : '';

        const roleBadge = isClient
            ? `<span class="keyword-badge bg-success bg-opacity-10 text-success">Our Client</span>${aioTag}`
            : isComp
                ? `<span class="keyword-badge competitor-tag">Competitor</span>${aioTag}`
                : aioTag;

        return `<tr class="${rowCls}">
            <td><span class="position-badge ${posClass(r.position)}">${r.position ?? '—'}</span></td>
            <td>
                <a href="${escapeHtml(r.link || '#')}" target="_blank" class="text-decoration-none fw-medium small">${escapeHtml(r.title || '—')}</a>
            </td>
            <td><a href="${escapeHtml(r.link || '#')}" target="_blank" class="result-link text-muted small d-block" title="${escapeHtml(r.link || '')}">${escapeHtml(r.link || '—')}</a></td>
            <td><span class="small">${escapeHtml(r.domain || extractDomain(r.link))}</span></td>
            <td><span class="snippet-cell text-muted small" style="cursor:pointer;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"
                data-title="${escapeHtml(r.title || '')}" data-url="${escapeHtml(r.link || '')}" data-snippet="${escapeHtml(r.snippet || '')}">${escapeHtml(r.snippet || '—')}</span></td>
            <td style="min-width:200px;">${roleBadge}</td>
        </tr>`;
    }).join('');
}

/* ══════════════════════════════════════════════════════════════════
   BUILD — Competitor table rows  (new: AI Overview column)
   ══════════════════════════════════════════════════════════════════ */
/* ══════════════════════════════════════════════════════════════════
   BUILD — Competitor table rows with Audit button
   ══════════════════════════════════════════════════════════════════ */
function buildCompetitor(rows, aioSources = []) {
    return rows.map(r => {
        // Match by domain so we get the AIO source object (with its title)
        const matchedSource = aioSources.find(s => domainMatch(r.link, s.link || ''));
        const inAio   = !!matchedSource;
        const aioTitle = matchedSource?.title || null;
        
        const aioCell = inAio
            ? `<div class="aio-comp-table-cell">
                   <span class="aio-comp-table-badge">
                       <i class="mdi mdi-robot-outline"></i> Featured
                   </span>
                   ${aioTitle
                        ? `<span class="aio-comp-table-cited" title="${escapeHtml(aioTitle)}">
                               "${escapeHtml(aioTitle)}"
                           </span>`
                        : ''}
               </div>`
            : `<span style="color:#d1d5db;font-size:.8rem;">—</span>`;
        
        // Add audit button with data attributes
        const auditBtn = `
            <button class="btn btn-sm btn-outline-primary audit-competitor-btn" 
                    data-url="${escapeHtml(r.link || '')}"
                    data-title="${escapeHtml(r.title || '')}"
                    style="font-size: 0.7rem; padding: 2px 8px;">
                <i class="mdi mdi-chart-line"></i> Audit
            </button>`;
        
        const rowCls = inAio ? 'aio-in-overview' : '';
        
        return `<tr class="${rowCls}">
            <td><span class="position-badge ${posClass(r.position)}">${r.position ?? '—'}</span></td>
            <td style="max-width:220px;">
                <a href="${escapeHtml(r.link || '#')}" target="_blank" class="text-decoration-none fw-medium small">${escapeHtml(r.title || '—')}</a>
            </td>
            <td>
                <a href="${escapeHtml(r.link || '#')}" target="_blank" class="result-link text-muted small d-block">${escapeHtml(r.link || '—')}</a>
                <div class="mt-1">
                    <span class="audit-loading" style="display:none;">
                        <span class="spinner-border spinner-border-sm text-primary me-1" role="status"></span>
                        <small class="text-muted">Auditing...</small>
                    </span>
                </div>
            </td>
            <td><span class="small">${escapeHtml(r.domain || extractDomain(r.link))}</span></td>
            <td>${aioCell}</td>
            <td>
                <div class="d-flex flex-column gap-1">
                    <span class="keyword-badge competitor-tag">Competitor</span>
                    ${auditBtn}
                </div>
             </td>
         </tr>`;
    }).join('');
}

/* ══════════════════════════════════════════════════════════════════
   SHARED: SNIPPET MODAL
   r_keywordTabsContent  = live ranking results (new analysis)
   us_rankingDateContent = saved summary ranking tabs (handled in viewUnifiedSummary above)
   ══════════════════════════════════════════════════════════════════ */
['r_keywordTabsContent'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('click', function(e) {
        const cell = e.target.closest('.snippet-cell');
        if (!cell) return;
        document.getElementById('snippetModalTitle').textContent = cell.dataset.title||'—';
        document.getElementById('snippetModalUrl').innerHTML = `<a href="${cell.dataset.url}" target="_blank" class="small">${cell.dataset.url}</a>`;
        document.getElementById('snippetModalBody').textContent = cell.dataset.snippet||'No description available.';
        new bootstrap.Modal(document.getElementById('snippetModal')).show();
    });
});

/* ══════════════════════════════════════════════════════════════════
   CSV EXPORT
   ══════════════════════════════════════════════════════════════════ */
document.getElementById('r_exportCsvBtn').addEventListener('click', () => exportActivePaneCsv('r_keywordTabsContent'));
function exportActivePaneCsv(contentId) {
    const activePane = document.querySelector(`#${contentId} .tab-pane.active table`);
    if (!activePane) return;
    const rows = [];
    activePane.querySelectorAll('thead tr').forEach(tr => {
        rows.push([...tr.querySelectorAll('th')].map(th=>`"${th.textContent.trim()}"`).join(','));
    });
    activePane.querySelectorAll('tbody tr').forEach(tr => {
        rows.push([...tr.querySelectorAll('td')].map(td=>`"${td.textContent.trim().replace(/"/g,'""')}"`).join(','));
    });
    const blob = new Blob([rows.join('\n')],{type:'text/csv'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `seo_report_${Date.now()}.csv`;
    a.click();
}

/* ══════════════════════════════════════════════════════════════════
   SHARED HELPERS
   ══════════════════════════════════════════════════════════════════ */
function showLiveError(msg) {
    console.error(msg);
    const msgEl     = document.getElementById('liveErrorMessage');
    const sectionEl = document.getElementById('liveErrorSection');
    if (!msgEl || !sectionEl) {
        // Elements missing — re-inject the banner into the page
        const banner = document.createElement('div');
        banner.id = 'liveErrorSection';
        banner.className = 'mb-3';
        banner.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert">
                <i class="mdi mdi-alert-circle me-2"></i>
                <span id="liveErrorMessage">${msg}</span>
                <button type="button" class="btn-close" onclick="this.closest('#liveErrorSection').style.display='none';"></button>
            </div>`;
        const anchor = document.getElementById('viewNewAudit') || document.body;
        anchor.prepend(banner);
        return;
    }
    msgEl.textContent = msg;
    sectionEl.style.display = 'block';
}
function posClass(pos) { if(!pos) return 'pos-other'; if(pos<=3) return 'pos-top3'; if(pos<=10) return 'pos-top10'; return 'pos-other'; }
function extractDomain(url) { try { return new URL(url).hostname.replace('www.',''); } catch { return '—'; } }
function domainMatch(link, input) {
    if (!link || !input) return false;

    const n = s => String(s)
        .toLowerCase()
        .replace(/https?:\/\/(www\.)?/, '')
        .replace(/\/$/, '')
        .split('/')[0];

    return n(link).includes(n(input)) || n(input).includes(n(link));
}
function escapeHtml(str) {
    if(!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
/* ══════════════════════════════════════════════════════════════════
   FORM: WEBSITE AUDIT — Submit
   ══════════════════════════════════════════════════════════════════ */


document.getElementById('wa_resetBtn').addEventListener('click', function() {
    document.getElementById('wa_resultsSection').style.display = 'none';
    document.getElementById('liveErrorSection').style.display = 'none';
    document.getElementById('wa_resultsContent').innerHTML = '';
    waAllData = {};
});

function waSetLoading(on) {
    document.getElementById('wa_generateBtn').disabled = on;
    document.getElementById('wa_generateBtnSpinner').classList.toggle('d-none', !on);
    document.getElementById('wa_generateBtnText').textContent = on ? 'Analysing…' : 'Analyse Website Health';
}

function renderJson(data, level = 0) {
    let html = '';
    for (const [key, value] of Object.entries(data)) {
        html += `<div style='margin-left:${level*20}px;color:white;'><b>${key}</b> = `;
        if (Array.isArray(value) || (typeof value === 'object' && value !== null)) {
            html += '</div>';
            html += renderJson(value, level + 1);
        } else {
            html += value + '</div>';
        }
    }
    return html;
}

function renderWebsiteAuditResults(data) {
    const observatory = data.audits?.mozilla_observatory || {};
    const pagespeed = data.audits?.pagespeed_insights || {};
    const domain = data.domain || 'Unknown';
    const siteUrl = data.site_url || '';
    
    const hasObservatory = !observatory.error;
    const hasPagespeed = pagespeed.mobile && !pagespeed.mobile.error;
    
    let html = `
    <div class="p-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
            <div>
                <h5 class="mb-1 fw-bold">Website Health Audit Results</h5>
                <p class="text-muted small mb-0">
                    <strong>${domain}</strong> • ${new Date().toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'})}
                </p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-secondary" onclick="window.open('${siteUrl}', '_blank')">
                    <i class="mdi mdi-open-in-new me-1"></i> Visit Site
                </button>
            </div>
        </div>
        
        <!-- Quick Overview Cards -->
        <div class="row g-3 mb-4">
            ${renderQuickOverviewCards(observatory, pagespeed, domain)}
        </div>
        
        <!-- Main Dynamic Tabs -->
        <ul class="nav nav-tabs mb-0" id="waMainTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="wa-tab-mozilla" data-bs-toggle="tab" data-bs-target="#wa-pane-mozilla" type="button" role="tab">
                    <i class="mdi mdi-shield-check me-1"></i> Mozilla Observatory
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="wa-tab-pagespeed" data-bs-toggle="tab" data-bs-target="#wa-pane-pagespeed" type="button" role="tab">
                    <i class="mdi mdi-speedometer me-1"></i> PageSpeed Insights
                </button>
            </li>
        </ul>
        
        <div class="tab-content border border-top-0 rounded-bottom p-3 bg-white" id="waMainContent">
            <!-- Mozilla Observatory Tab -->
            <div class="tab-pane fade show active" id="wa-pane-mozilla" role="tabpanel">
                ${renderMozillaObservatoryTab(observatory, domain)}
            </div>
            
            <!-- PageSpeed Insights Tab -->
            <div class="tab-pane fade" id="wa-pane-pagespeed" role="tabpanel">
                ${renderPageSpeedTab(pagespeed, domain)}
            </div>
        </div>
    </div>`;
    
    document.getElementById('wa_resultsContent').innerHTML = html;
}

function renderQuickOverviewCards(observatory, pagespeed, domain) {
    let cards = '';
    
    // Mozilla Observatory Card
    if (!observatory.error) {
        const grade = observatory.grade || '?';
        const score = observatory.score || 0;
        const gradeColor = getGradeColor(grade);
        
        cards += `
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2"><i class="mdi mdi-shield-check" style="font-size: 1.5rem; color: ${gradeColor};"></i></div>
                    <div style="width: 70px; height: 70px; border-radius: 50%; background: ${gradeColor}; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; font-weight: 800; color: white;">
                        ${grade}
                    </div>
                    <div class="mt-2 fw-semibold">Mozilla Observatory</div>
                    <div class="small text-muted">Security Grade: ${score}/100</div>
                    <div class="small text-muted">${observatory.tests_passed || 0}/${observatory.tests_quantity || 0} tests passed</div>
                </div>
            </div>
        </div>`;
    } else {
        cards += `
        <div class="col-md-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-2"><i class="mdi mdi-alert-circle text-warning" style="font-size: 1.5rem;"></i></div>
                    <div class="fw-semibold">Mozilla Observatory</div>
                    <div class="small text-muted">Scan unavailable</div>
                    <div class="small text-muted">${observatory.message || 'HTTP 502 Error'}</div>
                </div>
            </div>
        </div>`;
    }
    
    // PageSpeed Cards
    ['mobile', 'desktop'].forEach(strategy => {
        const data = pagespeed[strategy];
        if (data && !data.error) {
            const perfScore = data.category_scores?.performance?.score || 0;
            const scoreColor = getScoreColor(perfScore);
            const scoreLabel = perfScore >= 90 ? 'Good' : perfScore >= 50 ? 'Needs Work' : 'Poor';
            
            cards += `
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="mb-2"><i class="mdi mdi-${strategy === 'mobile' ? 'cellphone' : 'monitor'} text-muted" style="font-size: 1.5rem;"></i></div>
                        <div style="font-size: 2.5rem; font-weight: 800; color: ${scoreColor};">${perfScore}</div>
                        <div class="fw-semibold">${strategy === 'mobile' ? 'Mobile' : 'Desktop'} Performance</div>
                        <span class="badge mt-1" style="background: ${scoreColor}; color: white;">${scoreLabel}</span>
                    </div>
                </div>
            </div>`;
        }
    });
    
    return cards;
}

function renderMozillaObservatoryTab(observatory, domain) {
    if (observatory.error) {
        return `
        <div class="text-center py-5">
            <i class="mdi mdi-alert-circle text-warning" style="font-size: 3rem;"></i>
            <h5 class="mt-3">Observatory Scan Failed</h5>
            <p class="text-muted">${observatory.message || 'Unable to complete security scan'}</p>
            <div class="alert alert-warning d-inline-block text-start mt-2">
                <strong>Error Details:</strong><br>
                ${observatory.message || 'HTTP 502 Bad Gateway'}<br>
                <small class="text-muted">This could be due to the Mozilla Observatory API being temporarily unavailable. Try again later.</small>
            </div>
        </div>`;
    }
    
    const grade = observatory.grade || '?';
    const score = observatory.score || 0;
    const gradeColor = getGradeColor(grade);
    const passed = observatory.tests_passed || 0;
    const failed = observatory.tests_failed || 0;
    const total = observatory.tests_quantity || 0;
    
    let html = `
    <!-- Grade Summary -->
    <div class="row mb-4">
        <div class="col-md-4 text-center">
            <div style="width: 100px; height: 100px; border-radius: 50%; background: ${gradeColor}; display: inline-flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: 800; color: white;">
                ${grade}
            </div>
            <div class="mt-2 fw-bold">Security Grade</div>
            <div class="text-muted small">${observatory.score_description || 'Assessment by Mozilla'}</div>
        </div>
        <div class="col-md-8">
            <div class="row g-2">
                <div class="col-6">
                    <div class="border rounded p-3 text-center">
                        <div class="text-success fw-bold" style="font-size: 1.5rem;">${passed}</div>
                        <div class="small text-muted">Tests Passed</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded p-3 text-center">
                        <div class="text-danger fw-bold" style="font-size: 1.5rem;">${failed}</div>
                        <div class="small text-muted">Tests Failed</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded p-3 text-center">
                        <div class="text-primary fw-bold" style="font-size: 1.5rem;">${score}</div>
                        <div class="small text-muted">Score /100</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded p-3 text-center">
                        <div class="text-dark fw-bold" style="font-size: 1.5rem;">${total}</div>
                        <div class="small text-muted">Total Tests</div>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
    
    // Security Tests Table
    if (observatory.tests && Object.keys(observatory.tests).length > 0) {
        html += `
        <h6 class="fw-bold mb-3"><i class="mdi mdi-security me-1"></i> Security Tests Breakdown</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40%;">Test Name</th>
                        <th style="width: 20%;">Result</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 25%;">Score Modifier</th>
                    </tr>
                </thead>
                <tbody>`;
        
        Object.entries(observatory.tests).forEach(([name, test]) => {
            const passed = test.pass;
            const statusBadge = passed 
                ? '<span class="badge bg-success"><i class="mdi mdi-check-circle me-1"></i> PASS</span>' 
                : '<span class="badge bg-danger"><i class="mdi mdi-close-circle me-1"></i> FAIL</span>';
            const result = test.result || 'N/A';
            const modifier = test.score_modifier || 0;
            const modifierColor = modifier >= 0 ? 'text-success' : 'text-danger';
            const modifierSign = modifier >= 0 ? '+' : '';
            
            html += `<tr>
                <td class="fw-semibold">${formatTestName(name)}</td>
                <td><code class="bg-light px-2 py-1 rounded small">${escapeHtml(result)}</code></td>
                <td>${statusBadge}</td>
                <td><span class="${modifierColor} fw-semibold">${modifierSign}${modifier}</span></td>
            </tr>`;
        });
        
        html += `</tbody></table></div>`;
    }
    
    // Response Headers
    if (observatory.response_headers && Object.keys(observatory.response_headers).length > 0) {
        html += `
        <h6 class="fw-bold mb-3 mt-4"><i class="mdi mdi-code-tags me-1"></i> Response Headers</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead class="table-light">
                    <tr><th>Header</th><th>Value</th></tr>
                </thead>
                <tbody>`;
        
        Object.entries(observatory.response_headers).forEach(([header, value]) => {
            html += `<tr>
                <td class="fw-semibold small">${escapeHtml(header)}</td>
                <td class="small"><code>${escapeHtml(String(value))}</code></td>
            </tr>`;
        });
        
        html += `</tbody></table></div>`;
    }
    
    return html;
}

function renderPageSpeedTab(pagespeed, domain) {
    const hasMobile = pagespeed.mobile && !pagespeed.mobile.error;
    const hasDesktop = pagespeed.desktop && !pagespeed.desktop.error;
    
    if (!hasMobile && !hasDesktop) {
        return `
        <div class="text-center py-5">
            <i class="mdi mdi-alert-circle text-warning" style="font-size: 3rem;"></i>
            <h5 class="mt-3">PageSpeed Data Unavailable</h5>
            <p class="text-muted">Could not fetch PageSpeed Insights data. Please try again.</p>
        </div>`;
    }
    
    let html = `
    <!-- Sub-tabs for Mobile/Desktop -->
    <ul class="nav nav-pills mb-4" id="waPsSubTabs" role="tablist">
        ${hasMobile ? `
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="wa-pst-mobile" data-bs-toggle="tab" data-bs-target="#wa-psp-mobile" type="button" role="tab">
                <i class="mdi mdi-cellphone me-1"></i> Mobile
            </button>
        </li>` : ''}
        ${hasDesktop ? `
        <li class="nav-item" role="presentation">
            <button class="nav-link ${!hasMobile ? 'active' : ''}" id="wa-pst-desktop" data-bs-toggle="tab" data-bs-target="#wa-psp-desktop" type="button" role="tab">
                <i class="mdi mdi-monitor me-1"></i> Desktop
            </button>
        </li>` : ''}
    </ul>
    
    <div class="tab-content" id="waPsSubContent">`;
    
    // Mobile Tab Content
    if (hasMobile) {
        html += `
        <div class="tab-pane fade show active" id="wa-psp-mobile" role="tabpanel">
            ${renderPageSpeedDetail(pagespeed.mobile, 'mobile')}
        </div>`;
    }
    
    // Desktop Tab Content
    if (hasDesktop) {
        html += `
        <div class="tab-pane fade ${!hasMobile ? 'show active' : ''}" id="wa-psp-desktop" role="tabpanel">
            ${renderPageSpeedDetail(pagespeed.desktop, 'desktop')}
        </div>`;
    }
    
    html += `</div>`;
    return html;
}

function renderPageSpeedDetail(data, strategy) {
    const scores = data.category_scores || {};
    const vitals = data.core_web_vitals || {};
    const diagnostics = data.diagnostics || {};
    const opportunities = data.opportunities || {};
    const meta = data.meta || {};
    
    let html = '';
    
    // Category Scores
    html += `
    <div class="row g-3 mb-4">
        ${['performance', 'accessibility', 'best-practices', 'seo'].map(cat => {
            const score = scores[cat]?.score || 0;
            const scoreColor = getScoreColor(score);
            const scoreLabel = score >= 90 ? 'Good' : score >= 50 ? 'Needs Work' : 'Poor';
            return `
            <div class="col-md-3 col-6">
                <div class="border rounded p-3 text-center h-100">
                    <div style="font-size: 2rem; font-weight: 800; color: ${scoreColor};">${score}</div>
                    <div class="small fw-semibold">${formatCategoryName(cat)}</div>
                    <span class="badge mt-1" style="background: ${scoreColor}; color: white; font-size: 0.7rem;">${scoreLabel}</span>
                </div>
            </div>`;
        }).join('')}
    </div>`;
    
    // Core Web Vitals
    html += `
    <h6 class="fw-bold mb-3"><i class="mdi mdi-heart-pulse me-1"></i> Core Web Vitals</h6>
    <div class="table-responsive mb-4">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th style="width: 30%;">Metric</th>
                    <th style="width: 25%;">Value</th>
                    <th style="width: 20%;">Status</th>
                    <th style="width: 25%;">Threshold</th>
                </tr>
            </thead>
            <tbody>
                ${renderVitalRowsDetailed(vitals)}
            </tbody>
        </table>
    </div>`;
    
    // Opportunities (Issues to fix)
    if (opportunities && Object.keys(opportunities).length > 0) {
        html += `
        <h6 class="fw-bold mb-3"><i class="mdi mdi-lightbulb-on me-1"></i> Opportunities</h6>
        <div class="table-responsive mb-4">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Issue</th>
                        <th>Estimated Savings</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>`;
        
        Object.entries(opportunities).forEach(([key, opp]) => {
            const savings = opp.savings_ms ? `${(opp.savings_ms / 1000).toFixed(2)}s` : '—';
            html += `<tr>
                <td class="fw-semibold">${opp.title || formatTestName(key)}</td>
                <td><span class="badge bg-warning text-dark">${savings}</span></td>
                <td class="small text-muted">${opp.description || '—'}</td>
            </tr>`;
        });
        
        html += `</tbody></table></div>`;
    }
    
    // Diagnostics
    if (diagnostics && Object.keys(diagnostics).length > 0) {
        html += `
        <h6 class="fw-bold mb-3"><i class="mdi mdi-stethoscope me-1"></i> Diagnostics</h6>
        <div class="table-responsive mb-4">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Issue</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>`;
        
        Object.entries(diagnostics).slice(0, 15).forEach(([key, diag]) => {
            html += `<tr>
                <td class="fw-semibold">${diag.title || formatTestName(key)}</td>
                <td class="small text-muted">${diag.description || '—'}</td>
            </tr>`;
        });
        
        html += `</tbody></table></div>`;
    }
    
    // Field Data (CrUX)
    if (data.field_data_crux && Object.keys(data.field_data_crux).length > 0) {
        html += `
        <h6 class="fw-bold mb-3"><i class="mdi mdi-account-group me-1"></i> Real User Data (CrUX)</h6>
        <div class="alert alert-info small">
            <i class="mdi mdi-information me-1"></i>
            Based on real-world user data from Chrome User Experience Report
        </div>`;
    }
    
    // Meta Info
    if (meta.lighthouse_version) {
        html += `
        <div class="text-muted small mt-3">
            <i class="mdi mdi-information-outline me-1"></i>
            Lighthouse ${meta.lighthouse_version} • Analyzed at ${meta.fetch_time || 'N/A'}
        </div>`;
    }
    
    return html;
}

function renderVitalRowsDetailed(vitals) {
    if (!vitals || Object.keys(vitals).length === 0) {
        return '<tr><td colspan="4" class="text-muted text-center">No vital data available</td></tr>';
    }
    
    const vitalDefs = [
        { key: 'largest_contentful_paint', label: 'LCP', name: 'Largest Contentful Paint', good: 2.5, poor: 4.0, unit: 's' },
        { key: 'cumulative_layout_shift', label: 'CLS', name: 'Cumulative Layout Shift', good: 0.1, poor: 0.25, unit: '' },
        { key: 'total_blocking_time', label: 'TBT', name: 'Total Blocking Time', good: 200, poor: 600, unit: 'ms' },
        { key: 'interaction_to_next_paint', label: 'INP', name: 'Interaction to Next Paint', good: 200, poor: 500, unit: 'ms' },
        { key: 'first_contentful_paint', label: 'FCP', name: 'First Contentful Paint', good: 1.8, poor: 3.0, unit: 's' },
        { key: 'speed_index', label: 'SI', name: 'Speed Index', good: 3.4, poor: 5.8, unit: 's' },
        { key: 'time_to_interactive', label: 'TTI', name: 'Time to Interactive', good: 3.8, poor: 7.3, unit: 's' },
        { key: 'time_to_first_byte', label: 'TTFB', name: 'Time to First Byte', good: 0.8, poor: 1.8, unit: 's' },
    ];
    
    let rows = '';
    vitalDefs.forEach(def => {
        const vital = vitals[def.key];
        if (vital) {
            const display = vital.display_value || '—';
            const score = vital.score;
            const numericValue = vital.numeric_value;
            
            // Determine status
            let status, statusClass;
            if (score !== null && score !== undefined) {
                if (score >= 0.9) { status = 'Good'; statusClass = 'bg-success'; }
                else if (score >= 0.5) { status = 'Needs Work'; statusClass = 'bg-warning text-dark'; }
                else { status = 'Poor'; statusClass = 'bg-danger'; }
            } else if (numericValue !== null && numericValue !== undefined) {
                // Manual threshold check
                const valueInUnit = def.unit === 's' ? numericValue / 1000 : numericValue;
                if (valueInUnit <= def.good) { status = 'Good'; statusClass = 'bg-success'; }
                else if (valueInUnit <= def.poor) { status = 'Needs Work'; statusClass = 'bg-warning text-dark'; }
                else { status = 'Poor'; statusClass = 'bg-danger'; }
            } else {
                status = 'N/A'; statusClass = 'bg-secondary';
            }
            
            const threshold = `≤ ${def.good}${def.unit}`;
            
            rows += `<tr>
                <td>
                    <span class="fw-semibold">${def.label}</span>
                    <div class="small text-muted">${def.name}</div>
                </td>
                <td><strong>${display}</strong></td>
                <td><span class="badge ${statusClass}">${status}</span></td>
                <td class="small text-muted">${threshold}</td>
            </tr>`;
        }
    });
    
    return rows || '<tr><td colspan="4" class="text-muted text-center">No metrics available</td></tr>';
}

// Helper Functions
function getGradeColor(grade) {
    if (!grade) return '#6b7280';
    if (grade === 'A+') return '#22c55e';
    if (grade.startsWith('A')) return '#16a34a';
    if (grade.startsWith('B')) return '#eab308';
    if (grade.startsWith('C')) return '#f97316';
    return '#ef4444';
}

function getScoreColor(score) {
    if (!score && score !== 0) return '#6b7280';
    if (score >= 90) return '#22c55e';
    if (score >= 50) return '#eab308';
    return '#ef4444';
}

function formatTestName(name) {
    return name.replace(/-/g, ' ')
               .replace(/_/g, ' ')
               .replace(/\b\w/g, l => l.toUpperCase());
}

function formatCategoryName(cat) {
    const names = {
        'performance': 'Performance',
        'accessibility': 'Accessibility',
        'best-practices': 'Best Practices',
        'seo': 'SEO'
    };
    return names[cat] || cat;
}

/* ══════════════════════════════════════════════════════════════════
   COMPETITOR AUDIT MODAL / PANEL
   ══════════════════════════════════════════════════════════════════ */


/**
 * Fetch SEO audit for a competitor URL using the SEOTech API
 */
async function fetchCompetitorAudit(url) {
    // Check cache first
    if (competitorAuditCache[url]) {
        return competitorAuditCache[url];
    }
    
    try {
        // Show loading indicator in the competitor table cell
        const loadingIndicator = document.querySelector(`[data-competitor-url="${escapeHtml(url)}"] .audit-loading`);
        if (loadingIndicator) loadingIndicator.style.display = 'inline-block';
        
        // const apiUrl = 'https://seotech.ichelon.in/api/audit';

        const response = await fetch('/audit-competitor', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF,  // ✅ already defined at line 1102
            },
            body: JSON.stringify({ url: url })
        });

        console.log('response', response);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        
        // Store the audit data in cache
        competitorAuditCache[url] = {
            success: true,
            data: data.data || data,
            fetched_at: new Date().toISOString()
        };
        
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        
        return competitorAuditCache[url];
        
    } catch (error) {
        console.error(`Failed to fetch audit for ${url}:`, error);
        competitorAuditCache[url] = {
            success: false,
            error: error.message,
            data: null
        };
        
        const loadingIndicator = document.querySelector(`[data-competitor-url="${escapeHtml(url)}"] .audit-loading`);
        if (loadingIndicator) loadingIndicator.style.display = 'none';
        
        return competitorAuditCache[url];
    }
}

/**
 * Render competitor audit modal content
 */
function renderCompetitorAuditModal(auditData, url, title) {
    if (!auditData || !auditData.success || !auditData.data) {
        return `
        <div class="text-center py-5">
            <i class="mdi mdi-alert-circle text-danger" style="font-size: 3rem;"></i>
            <h5 class="mt-3">Audit Failed</h5>
            <p class="text-muted">${auditData?.error || 'Unable to fetch audit data for this competitor.'}</p>
            <button class="btn btn-sm btn-outline-primary mt-2" onclick="fetchCompetitorAudit('${escapeHtml(url)}').then(() => openCompetitorAuditModal('${escapeHtml(url)}', '${escapeHtml(title)}'))">
                <i class="mdi mdi-refresh me-1"></i> Retry
            </button>
        </div>`;
    }
    
    const data = auditData.data;

    
    if (data.error_message ) {
        return `
        <div class="text-center py-5">
            <i class="mdi mdi-alert-circle text-danger" style="font-size: 3rem;"></i>
            <h5 class="mt-3">Audit Failed</h5>
            <p class="text-muted">${data?.error_message || 'Unable to fetch audit data for this competitor.'}</p>
            <button class="btn btn-sm btn-outline-primary mt-2" onclick="fetchCompetitorAudit('${escapeHtml(url)}').then(() => openCompetitorAuditModal('${escapeHtml(url)}', '${escapeHtml(title)}'))">
                <i class="mdi mdi-refresh me-1"></i> Retry
            </button>
        </div>`;
    }
    const overallScore = data.overall_score || 0;
    const categoryScores = data.category_scores || {};
    const counts = data.counts || { passed: 0, warning: 0, failed: 0, critical: 0 };
    const results = data.results || {};
    
    // Score color
    const scoreColor = overallScore >= 80 ? '#22c55e' : overallScore >= 60 ? '#eab308' : '#ef4444';
    const scoreLabel = overallScore >= 80 ? 'Good' : overallScore >= 60 ? 'Needs Work' : 'Poor';
    
    // Category list with scores
    const categories = [
        { key: 'meta', name: 'Meta Tags', icon: 'mdi-tag' },
        { key: 'headings', name: 'Headings', icon: 'mdi-format-header-1' },
        { key: 'schema', name: 'Schema', icon: 'mdi-code-json' },
        { key: 'technical', name: 'Technical', icon: 'mdi-cog' },
        { key: 'sitemap', name: 'Sitemap', icon: 'mdi-sitemap' },
        { key: 'ssl_security', name: 'SSL Security', icon: 'mdi-shield-check' },
        { key: 'page_speed', name: 'Page Speed', icon: 'mdi-speedometer' },
        { key: 'links', name: 'Links', icon: 'mdi-link-variant' },
        { key: 'images', name: 'Images', icon: 'mdi-image' },
        { key: 'content', name: 'Content', icon: 'mdi-text-box' },
        { key: 'mobile', name: 'Mobile', icon: 'mdi-cellphone' }
    ];
    
    let categoriesHtml = '';
    categories.forEach(cat => {
        const score = categoryScores[cat.key] || 0;
        const catColor = score >= 80 ? 'success' : score >= 60 ? 'warning' : 'danger';
        const catBg = score >= 80 ? '#dcfce7' : score >= 60 ? '#fef9c3' : '#fee2e2';
        const catTextColor = score >= 80 ? '#15803d' : score >= 60 ? '#b45309' : '#b91c1c';
        
        categoriesHtml += `
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="border rounded p-3 text-center h-100" style="background: ${catBg};">
                <i class="mdi ${cat.icon}" style="font-size: 1.5rem; color: ${catTextColor};"></i>
                <div class="fw-semibold small mt-1">${cat.name}</div>
                <div class="fw-bold" style="font-size: 1.3rem; color: ${catTextColor};">${score}</div>
            </div>
        </div>`;
    });
    
    // Build issue list
    let issuesHtml = '';
    const allIssues = [];
    
    // Collect all failed/warning checks
    for (const [category, checks] of Object.entries(results)) {
        if (Array.isArray(checks)) {
            checks.forEach(check => {
                if (check.status === 'fail' || check.status === 'warning') {
                    allIssues.push({
                        category: category,
                        check_name: check.check_name,
                        severity: check.severity,
                        status: check.status,
                        message: check.message,
                        recommendation: check.recommendation,
                        fix: check.fix
                    });
                }
            });
        }
    }
    
    // Sort by severity (critical > high > medium > low)
    const severityOrder = { 'critical': 0, 'high': 1, 'medium': 2, 'low': 3 };
    allIssues.sort((a, b) => (severityOrder[a.severity] || 4) - (severityOrder[b.severity] || 4));
    
    // Group by status
    const failIssues = allIssues.filter(i => i.status === 'fail');
    const warnIssues = allIssues.filter(i => i.status === 'warning');
    
    if (allIssues.length === 0) {
        issuesHtml = `
        <div class="text-center py-4">
            <i class="mdi mdi-check-circle text-success" style="font-size: 2rem;"></i>
            <p class="mt-2 text-muted">No issues detected! Great job! 🎉</p>
        </div>`;
    } else {
        // Severity badge colors
        const severityClass = {
            'critical': 'bg-danger',
            'high': 'bg-danger bg-opacity-75',
            'medium': 'bg-warning text-dark',
            'low': 'bg-info text-dark'
        };
        
        issuesHtml = `
        <div class="mb-3">
            <span class="badge bg-danger me-2">${failIssues.length} Failing</span>
            <span class="badge bg-warning text-dark">${warnIssues.length} Warnings</span>
        </div>
        <div class="list-group">`;
        
        allIssues.slice(0, 20).forEach(issue => {
            issuesHtml += `
            <div class="list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-between align-items-start">
                    <div>
                        <span class="badge ${severityClass[issue.severity] || 'bg-secondary'} me-2">${issue.severity?.toUpperCase() || 'INFO'}</span>
                        <span class="fw-semibold">${issue.check_name}</span>
                    </div>
                    <small class="text-muted">${issue.category}</small>
                </div>
                <p class="mb-1 small mt-2">${issue.message || '—'}</p>
                ${issue.recommendation ? `<p class="mb-0 small text-muted"><i class="mdi mdi-lightbulb-on-outline me-1"></i>${issue.recommendation}</p>` : ''}
            </div>`;
        });
        
        if (allIssues.length > 20) {
            issuesHtml += `<div class="list-group-item text-center text-muted small">+ ${allIssues.length - 20} more issues</div>`;
        }
        
        issuesHtml += `</div>`;
    }
    
    return `
    <div class="competitor-audit-content">
        <!-- Header with overall score -->
        <div class="text-center mb-4">
            <div style="width: 100px; height: 100px; border-radius: 50%; background: ${scoreColor}; 
                        display: inline-flex; align-items: center; justify-content: center; 
                        font-size: 2.2rem; font-weight: 800; color: white;">
                ${overallScore}
            </div>
            <div class="mt-2">
                <span class="badge" style="background: ${scoreColor};">${scoreLabel}</span>
            </div>
            <p class="text-muted small mt-2 mb-0">
                <i class="mdi mdi-clock-outline me-1"></i>
                ${data.completed_at ? new Date(data.completed_at).toLocaleString() : 'Recently'}
            </p>
        </div>
        
        <!-- Category Scores Grid -->
        <h6 class="fw-bold mb-3"><i class="mdi mdi-chart-donut me-1"></i> Category Scores</h6>
        <div class="row g-2 mb-4">
            ${categoriesHtml}
        </div>
        
        <!-- Issues / Fix Guide -->
        <h6 class="fw-bold mb-3"><i class="mdi mdi-bug me-1"></i> Issues & Fixes</h6>
        ${issuesHtml}
        
        <!-- View Full Report Link -->
        <div class="mt-3 text-center">
            <a href="${data.links?.self_html || '#'}" target="_blank" class="btn btn-sm btn-outline-primary">
                <i class="mdi mdi-open-in-new me-1"></i> View Full Report
            </a>
            <a href="${data.links?.pdf || '#'}" target="_blank" class="btn btn-sm btn-outline-secondary ms-2">
                <i class="mdi mdi-file-pdf me-1"></i> Download PDF
            </a>
        </div>
    </div>`;
}

/**
 * Open modal with competitor audit results
 */
async function openCompetitorAuditModal(url, title) {
    // Check if modal exists, create if not
    let modal = document.getElementById('competitorAuditModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'competitorAuditModal'; 
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        modal.innerHTML = `
        <div class="modal-dialog modal-fullscreen modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="mdi mdi-chart-line me-2"></i> <span id="auditModalTitle">SEO Audit</span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="auditModalBody" style="overflow-y: auto;">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-2">Fetching audit data...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>`;
        document.body.appendChild(modal);
    }
    
    // Update title
    document.getElementById('auditModalTitle').innerHTML = `<i class="mdi mdi-domain me-1"></i> ${escapeHtml(title || url)}`;
    
    // Show loading
    document.getElementById('auditModalBody').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2">Fetching SEO audit for ${escapeHtml(url)}...</p>
            <p class="text-muted small">This may take 10-30 seconds</p>
        </div>`;
    
    // Show modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Fetch audit data
    const auditData = await fetchCompetitorAudit(url);
    
    // Render content
    document.getElementById('auditModalBody').innerHTML = renderCompetitorAuditModal(auditData, url, title);
}
// Add to the existing snippet modal listener block
['r_keywordTabsContent', 'us_rankingDateContent'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    
    // Existing snippet listener
    el.addEventListener('click', function(e) {
        const cell = e.target.closest('.snippet-cell');
        if (cell) {
            document.getElementById('snippetModalTitle').textContent = cell.dataset.title||'—';
            document.getElementById('snippetModalUrl').innerHTML = `<a href="${cell.dataset.url}" target="_blank" class="small">${cell.dataset.url}</a>`;
            document.getElementById('snippetModalBody').textContent = cell.dataset.snippet||'No description available.';
            new bootstrap.Modal(document.getElementById('snippetModal')).show();
        }
    });
});
</script>
@endpush