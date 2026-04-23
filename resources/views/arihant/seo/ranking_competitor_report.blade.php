@extends('layouts.app')

@section('title', 'Ranking and Competitor Report')
@section('page_header', 'Ranking and Competitor Report')
@section('page_icon', 'mdi mdi-format-list-bulleted-square')

@section('breadcrumb')
    <li class="breadcrumb-item active">SEO</li>
    <li class="breadcrumb-item active" aria-current="page">Ranking and Competitor Report</li>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .keyword-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-right: 4px;
    }
    .position-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        font-weight: 700;
        font-size: 0.8rem;
    }
    .pos-top3  { background: #d1fae5; color: #065f46; }
    .pos-top10 { background: #dbeafe; color: #1e40af; }
    .pos-other { background: #f3f4f6; color: #6b7280; }
    .result-link { max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .competitor-tag { background: #fef3c7; color: #92400e; }
    #keywordTabs .nav-link { font-size: 0.85rem; }
    .dataTables_wrapper .dataTables_filter input { border-radius: 6px; border: 1px solid #dee2e6; padding: 4px 10px; }

    /* Saved report section */
    #savedSection .card-title { font-size: 1rem; }
    #savedReportSelect { max-width: 600px; }
    #savedResultsDisplay { display: none; }
    .save-status { font-size: 0.8rem; }
</style>
@endpush

@section('content')

<div class="row g-4">
    
    @if($savedReports->isNotEmpty())
    <div class="col-12" id="savedSection">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-1">
                    <i class="mdi mdi-history me-1 text-primary"></i>
                    Saved Ranking &amp; Competitor Report Results
                </h5>
                <p class="card-subtitle mb-3">Select a previously generated report to view its results.</p>

                    <div class="d-flex align-items-center gap-3 mb-4">
                        <select class="form-select" id="savedReportSelect" style="max-width:600px;">
                            <option value="" selected disabled>— Select a saved report —</option>
                            @foreach($savedReports as $report)
                                <option value="{{ $report['id'] }}">{{ $report['label'] }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline-danger btn-sm d-none" id="clearSavedBtn">
                            <i class="mdi mdi-close me-1"></i>Clear
                        </button>
                    </div>

                    {{-- Saved results display area --}}
                    <div id="savedResultsDisplay">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="fw-semibold mb-0" id="savedResultsTitle"></h6>
                                <p class="text-muted small mb-0" id="savedResultsSubtitle"></p>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary" id="exportSavedCsvBtn">
                                <i class="mdi mdi-download me-1"></i> Export CSV
                            </button>
                        </div>
                        <ul class="nav nav-tabs mb-3" id="savedKeywordTabs" role="tablist"></ul>
                        <div class="tab-content" id="savedKeywordTabsContent"></div>
                    </div>
            </div>
        </div>
    </div>
    @endif
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Ranking and Competitor Report Auto-pull</h5>
                <p class="card-subtitle">Configure location, keywords and competitor analysis options</p>

                <form id="rankingCompetitorReportForm" novalidate>
                    @csrf
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Location <span class="text-danger">*</span></label>
                                <select class="form-select" id="locationSelect" name="location" required>
                                    <option value="" disabled selected>Select location</option>
                                    <option value="Delhi">Delhi</option>
                                    <option value="Gurgaon">Gurgaon</option>
                                    <option value="Noida">Noida</option>
                                    <option value="Faridabad">Faridabad</option>
                                    <option value="Ghaziabad">Ghaziabad</option>
                                </select>
                                <div class="invalid-feedback">Please select a location.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Keywords <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="keywordsInput" name="keywords"
                                    placeholder="Enter multiple keywords separated by commas" required>
                                <small class="text-muted">Separate each keyword with a comma</small>
                                <div class="invalid-feedback">Please enter at least one keyword.</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">Our Client <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="ourclientInput" name="ourclient"
                                    placeholder="Enter the Client domain" value="{{ $domain }}" required>
                                <div class="invalid-feedback">Please enter the client domain.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="row">
                            {{-- Radio 1: Define Competitor Domain --}}
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="competitorOption"
                                        id="defineCompetitorCheckbox" value="define">
                                    <label class="form-check-label fw-semibold" for="defineCompetitorCheckbox">
                                        Define Competitor Domain
                                    </label>
                                </div>
                                <div id="competitorDomainsInput" style="display: none;">
                                    <div class="form-group">
                                        <label class="form-label">Competitor URLs</label>
                                        <input type="text" class="form-control" id="competitorUrls" name="competitor_urls"
                                            placeholder="Enter competitor URLs separated by commas">
                                        <small class="text-muted">Separate each URL with a comma</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Radio 2: Auto check competitor --}}
                            <div class="col-md-6">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="radio" name="competitorOption"
                                        id="autoCheckCompetitorCheckbox" value="auto">
                                    <label class="form-check-label fw-semibold" for="autoCheckCompetitorCheckbox">
                                        Auto check competitor
                                    </label>
                                    <p class="small text-muted mt-1 mb-0">
                                        System will automatically detect and analyze competitors based on keywords.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 mt-4">
                        <button type="submit" class="btn btn-primary" id="generateBtn">
                            <span id="generateBtnText">Generate Report</span>
                            <span id="generateBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                        </button>
                        <button type="reset" class="btn btn-light" id="resetBtn">Reset</button>
                        {{-- Save status indicator (shown after auto-save) --}}
                        <span id="saveStatus" class="save-status d-none"></span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         LIVE RESULTS CARD (shown after Generate Report)
    ════════════════════════════════════════════════════════════════════════ --}}
    <div class="col-12" id="resultsSection" style="display: none;">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="card-title mb-0">Search Ranking and Competitor Results</h5>
                        <p class="card-subtitle mt-1" id="resultsSubtitle"></p>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary" id="exportCsvBtn">
                        <i class="mdi mdi-download me-1"></i> Export CSV
                    </button>
                </div>

                <ul class="nav nav-tabs mb-3" id="keywordTabs" role="tablist"></ul>
                <div class="tab-content" id="keywordTabsContent"></div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         ERROR ALERT
    ════════════════════════════════════════════════════════════════════════ --}}
    <div class="col-12" id="errorSection" style="display: none;">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-2"></i>
            <span id="errorMessage">An error occurred. Please try again.</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>

    {{-- Snippet modal --}}
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
</div>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
// ─── Blade → JS data ──────────────────────────────────────────────────────────
const SAVE_URL             = '{{ route("ranking.competitor.report.save") }}';
const FORM_URL             = '{{ route("ranking.competitor.report.form", [$created_by_user_id, $client_property_id]) }}';
const CSRF                 = document.querySelector('input[name="_token"]').value;
const created_by_user_id   = {{ $created_by_user_id }};
const CLIENT_PROPERTY_ID   = {{ $client_property_id }};

// All saved reports injected from the controller (keyed by id)
const SAVED_REPORTS_MAP = {};
@foreach($savedReports as $report)
SAVED_REPORTS_MAP[{{ $report['id'] }}] = @json($report);
@endforeach

// ─── Competitor toggle ────────────────────────────────────────────────────────
document.getElementById('defineCompetitorCheckbox').addEventListener('change', function () {
    document.getElementById('competitorDomainsInput').style.display = this.checked ? 'block' : 'none';
});
document.getElementById('autoCheckCompetitorCheckbox').addEventListener('change', function () {
    if (this.checked) document.getElementById('competitorDomainsInput').style.display = 'none';
});
document.getElementById('resetBtn').addEventListener('click', function () {
    document.getElementById('competitorDomainsInput').style.display = 'none';
    document.getElementById('resultsSection').style.display = 'none';
    document.getElementById('errorSection').style.display = 'none';
    document.getElementById('keywordTabs').innerHTML = '';
    document.getElementById('keywordTabsContent').innerHTML = '';
    document.getElementById('saveStatus').className = 'save-status d-none';
    destroyAllTables();
});

// ─── DataTable registry ───────────────────────────────────────────────────────
const dtInstances = {};
function destroyAllTables() {
    Object.keys(dtInstances).forEach(k => {
        if (dtInstances[k]) { dtInstances[k].destroy(); delete dtInstances[k]; }
    });
}

// ─── Form submit ──────────────────────────────────────────────────────────────
document.getElementById('rankingCompetitorReportForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const location       = document.getElementById('locationSelect').value;
    const keywords       = document.getElementById('keywordsInput').value.trim();
    const competitor     = document.querySelector('input[name="competitorOption"]:checked')?.value || '';
    const competitorUrls = document.getElementById('competitorUrls').value.trim();

    let valid = true;
    if (!location) { document.getElementById('locationSelect').classList.add('is-invalid'); valid = false; }
    else             document.getElementById('locationSelect').classList.remove('is-invalid');
    if (!keywords)  { document.getElementById('keywordsInput').classList.add('is-invalid'); valid = false; }
    else             document.getElementById('keywordsInput').classList.remove('is-invalid');
    if (!valid) return;

    setLoading(true);
    document.getElementById('errorSection').style.display = 'none';
    document.getElementById('resultsSection').style.display = 'none';
    document.getElementById('saveStatus').className = 'save-status d-none';

    const keywordList    = keywords.split(',').map(k => k.trim()).filter(Boolean);
    const competitorList = competitorUrls ? competitorUrls.split(',').map(u => u.trim()).filter(Boolean) : [];
    const ourClient      = document.getElementById('ourclientInput').value.trim();

    try {
        const promises = keywordList.map(kw =>
            fetch(FORM_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body: JSON.stringify({ keyword: kw, location, competitor_option: competitor, competitor_urls: competitorList }),
            }).then(r => r.json()).then(data => ({ keyword: kw, data }))
        );

        const results = await Promise.all(promises);
        renderResults(results, location, competitorList, ourClient, competitor,
                      'keywordTabs', 'keywordTabsContent', dtInstances);

        document.getElementById('resultsSubtitle').textContent =
            `Location: ${location} · ${results.length} keyword(s) analyzed`;
        document.getElementById('resultsSection').style.display = 'block';
        document.getElementById('resultsSection').scrollIntoView({ behavior: 'smooth' });

        // ── Auto-save to DB ──────────────────────────────────────────────────
        await saveReport(results, location, keywordList, ourClient, competitor);

    } catch (err) {
        showError('Failed to fetch results: ' + err.message);
    } finally {
        setLoading(false);
    }
});

// ─── Save report to DB ────────────────────────────────────────────────────────
async function saveReport(results, location, keywordList, clientDomain, competitorOption) {
    const el = document.getElementById('saveStatus');
    try {
        el.className = 'save-status text-muted';
        el.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving…';

        const resp = await fetch(SAVE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({
                created_by_user_id: created_by_user_id,
                client_property_id:  CLIENT_PROPERTY_ID,
                location,
                keywords:           keywordList,
                client_domain:      clientDomain,
                competitor_option:  competitorOption || null,
                results_json:       results,            // [{keyword, data:{organic_results:[]}}]
            }),
        });

        const json = await resp.json();
        if (json.success) {
            el.className = 'save-status text-success';
            el.innerHTML = '<i class="mdi mdi-check-circle me-1"></i> Report saved.';

            // Prepend to the saved reports dropdown without page reload
            prependToSavedDropdown(json.report);
        } else {
            throw new Error(json.message || 'Save failed');
        }
    } catch (err) {
        el.className = 'save-status text-danger';
        el.innerHTML = '<i class="mdi mdi-alert-circle me-1"></i> Could not save: ' + err.message;
    }
}

// Inject newly saved report into the dropdown + map dynamically
function prependToSavedDropdown(report) {
    SAVED_REPORTS_MAP[report.id] = report;

    const sel = document.getElementById('savedReportSelect');
    if (!sel) return; // section not shown (no saved reports were passed from server — rare edge case)

    const opt = document.createElement('option');
    opt.value       = report.id;
    opt.textContent = report.label;
    // Insert after the placeholder option (index 0)
    sel.insertBefore(opt, sel.options[1] || null);
}

// ─── Saved report: select handler ────────────────────────────────────────────
const savedDtInstances = {};
const savedSelectEl    = document.getElementById('savedReportSelect');

if (savedSelectEl) {
    savedSelectEl.addEventListener('change', function () {
        const id     = parseInt(this.value);
        const report = SAVED_REPORTS_MAP[id];
        if (!report) return;

        // Destroy previous saved DT instances
        Object.keys(savedDtInstances).forEach(k => {
            if (savedDtInstances[k]) { savedDtInstances[k].destroy(); delete savedDtInstances[k]; }
        });

        document.getElementById('savedKeywordTabs').innerHTML = '';
        document.getElementById('savedKeywordTabsContent').innerHTML = '';

        document.getElementById('savedResultsTitle').textContent =
            `Report: ${report.location}`;
        document.getElementById('savedResultsSubtitle').textContent =
            `${(report.keywords || []).join(', ')} · ${report.results_json.length} keyword(s)`;

        renderResults(
            report.results_json,
            report.location,
            [],                         // competitor list is encoded inside results_json already
            report.client_domain || '',
            report.competitor_option || '',
            'savedKeywordTabs',
            'savedKeywordTabsContent',
            savedDtInstances
        );

        document.getElementById('savedResultsDisplay').style.display = 'block';
        document.getElementById('clearSavedBtn').classList.remove('d-none');
        document.getElementById('savedResultsDisplay').scrollIntoView({ behavior: 'smooth' });
    });
}

const clearSavedBtn = document.getElementById('clearSavedBtn');
if (clearSavedBtn) {
    clearSavedBtn.addEventListener('click', function () {
        if (savedSelectEl) savedSelectEl.value = '';
        document.getElementById('savedResultsDisplay').style.display = 'none';
        clearSavedBtn.classList.add('d-none');
        Object.keys(savedDtInstances).forEach(k => {
            if (savedDtInstances[k]) { savedDtInstances[k].destroy(); delete savedDtInstances[k]; }
        });
        document.getElementById('savedKeywordTabs').innerHTML = '';
        document.getElementById('savedKeywordTabsContent').innerHTML = '';
    });
}

// ─── Render results (shared between live and saved) ───────────────────────────
/**
 * @param {Array}  results         [{keyword, data:{organic_results:[]}}]
 * @param {string} location
 * @param {Array}  competitorList  explicit competitor URLs (for 'define' mode)
 * @param {string} clientDomain
 * @param {string} competitorMode  'auto' | 'define' | ''
 * @param {string} tabsElId        DOM id of the <ul> tabs element
 * @param {string} contentElId     DOM id of the tab-content element
 * @param {object} dtRegistry      object to register DataTable instances into
 */
function renderResults(results, location, competitorList, clientDomain, competitorMode,
                       tabsElId, contentElId, dtRegistry) {

    const tabsEl    = document.getElementById(tabsElId);
    const contentEl = document.getElementById(contentElId);

    results.forEach((res, idx) => {
        const safeName = `${tabsElId}_kw_${idx}`;
        const isFirst  = idx === 0;

        // Tab button
        const li = document.createElement('li');
        li.className = 'nav-item';
        li.innerHTML = `
            <button class="nav-link ${isFirst ? 'active' : ''}"
                    id="tab-${safeName}" data-bs-toggle="tab"
                    data-bs-target="#pane-${safeName}" type="button" role="tab">
                ${escapeHtml(res.keyword)}
                <span class="badge bg-secondary ms-1">${(res.data?.organic_results || []).length}</span>
            </button>`;
        tabsEl.appendChild(li);

        // Pane
        const pane = document.createElement('div');
        pane.className = `tab-pane fade ${isFirst ? 'show active' : ''}`;
        pane.id = `pane-${safeName}`;
        pane.setAttribute('role', 'tabpanel');

        const allRows   = res.data?.organic_results || [];
        const clientRow = allRows.find(r => domainMatch(r.link, clientDomain));
        const clientPos = clientRow?.position ?? Infinity;

        let competitorRows;
        if (competitorMode === 'auto') {
            competitorRows = allRows.filter(r => r.position < clientPos && !domainMatch(r.link, clientDomain));
        } else if (competitorMode === 'define') {
            competitorRows = allRows.filter(r => competitorList.some(c => domainMatch(r.link, c)));
        } else {
            competitorRows = [];
        }

        const orgTableId  = `dt-org-${safeName}`;
        const compTableId = `dt-comp-${safeName}`;

        pane.innerHTML = `
            <h6 class="fw-semibold mb-2 mt-1">
                <i class="mdi mdi-magnify me-1 text-primary"></i> Organic Results
                <span class="badge bg-secondary ms-1">${allRows.length}</span>
            </h6>
            <p class="small text-muted mb-2">Click on a snippet to view the full description.</p>
            <div class="table-responsive mb-5">
                <table id="${orgTableId}" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr><th>Position</th><th>Title</th><th>URL</th><th>Domain</th><th>Snippet</th><th>Role</th></tr>
                    </thead>
                    <tbody>${buildOrganic(allRows, competitorRows, clientDomain)}</tbody>
                </table>
            </div>
            <h6 class="fw-semibold mb-2">
                <i class="mdi mdi-chart-bar me-1 text-warning"></i> Competitor Analysis
                <span class="badge bg-secondary ms-1">${competitorRows.length}</span>
            </h6>
            <div class="table-responsive">
                <table id="${compTableId}" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr><th>Position</th><th>Title</th><th>URL</th><th>Domain</th><th>Role</th></tr>
                    </thead>
                    <tbody>${buildCompetitor(competitorRows)}</tbody>
                </table>
            </div>`;

        contentEl.appendChild(pane);

        setTimeout(() => {
            dtRegistry[orgTableId] = $(`#${orgTableId}`).DataTable({
                pageLength: 25, order: [[0, 'asc']],
                columnDefs: [{ orderable: false, targets: [4, 5] }],
                language: { search: 'Filter:' },
                dom: '<"d-flex justify-content-between align-items-center mb-2"lf>rtip',
            });
            dtRegistry[compTableId] = $(`#${compTableId}`).DataTable({
                pageLength: 25, order: [[0, 'asc']],
                columnDefs: [{ orderable: false, targets: [4] }],
                language: { search: 'Filter:' },
                dom: '<"d-flex justify-content-between align-items-center mb-2"lf>rtip',
            });
        }, 100);
    });
}

// ─── Row builders ─────────────────────────────────────────────────────────────
function buildOrganic(rows, competitorRows, clientDomain) {
    return rows.map(r => {
        const isClient = domainMatch(r.link, clientDomain);
        const isComp   = competitorRows.some(c => c.link === r.link);
        const roleBadge = isClient
            ? '<span class="keyword-badge bg-success bg-opacity-10 text-success">Our Client</span>'
            : isComp
                ? '<span class="keyword-badge competitor-tag">Competitor</span>'
                : '';
        return `
        <tr class="${isClient ? 'table-success' : ''}">
            <td><span class="position-badge ${posClass(r.position)}">${r.position ?? '—'}</span></td>
            <td style="max-width:200px;">
                <a href="${escapeHtml(r.link||'#')}" target="_blank" class="text-decoration-none fw-medium small">
                    ${escapeHtml(r.title||'—')}
                </a>
            </td>
            <td>
                <a href="${escapeHtml(r.link||'#')}" target="_blank"
                   class="result-link text-muted small d-block" title="${escapeHtml(r.link||'')}">
                    ${escapeHtml(r.link||'—')}
                </a>
            </td>
            <td><span class="small">${escapeHtml(r.domain||extractDomain(r.link))}</span></td>
            <td>
                <span class="snippet-cell text-muted small"
                    style="cursor:pointer;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"
                    data-title="${escapeHtml(r.title||'')}"
                    data-url="${escapeHtml(r.link||'')}"
                    data-snippet="${escapeHtml(r.snippet||'')}">
                    ${escapeHtml(r.snippet||'—')}
                </span>
            </td>
            <td>${roleBadge}</td>
        </tr>`;
    }).join('');
}

function buildCompetitor(rows) {
    return rows.map(r => `
        <tr>
            <td><span class="position-badge ${posClass(r.position)}">${r.position ?? '—'}</span></td>
            <td style="max-width:220px;">
                <a href="${escapeHtml(r.link||'#')}" target="_blank" class="text-decoration-none fw-medium small">
                    ${escapeHtml(r.title||'—')}
                </a>
            </td>
            <td>
                <a href="${escapeHtml(r.link||'#')}" target="_blank"
                   class="result-link text-muted small d-block" title="${escapeHtml(r.link||'')}">
                    ${escapeHtml(r.link||'—')}
                </a>
            </td>
            <td><span class="small">${escapeHtml(r.domain||extractDomain(r.link))}</span></td>
            <td><span class="keyword-badge competitor-tag">Competitor</span></td>
        </tr>`).join('');
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function posClass(pos) {
    if (!pos)    return 'pos-other';
    if (pos <= 3)  return 'pos-top3';
    if (pos <= 10) return 'pos-top10';
    return 'pos-other';
}
function extractDomain(url) {
    try { return new URL(url).hostname.replace('www.', ''); } catch { return '—'; }
}
function domainMatch(link, input) {
    if (!link || !input) return false;
    const n = s => s.toLowerCase().replace(/https?:\/\/(www\.)?/, '').replace(/\/$/, '').split('/')[0];
    return n(link).includes(n(input)) || n(input).includes(n(link));
}
function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function setLoading(on) {
    document.getElementById('generateBtn').disabled = on;
    document.getElementById('generateBtnSpinner').classList.toggle('d-none', !on);
    document.getElementById('generateBtnText').textContent = on ? 'Generating…' : 'Generate Report';
}
function showError(msg) {
    document.getElementById('errorMessage').textContent = msg;
    document.getElementById('errorSection').style.display = 'block';
}

// ─── CSV Export (live results) ────────────────────────────────────────────────
document.getElementById('exportCsvBtn').addEventListener('click', () => exportActivePaneCsv('keywordTabsContent'));

const exportSavedBtn = document.getElementById('exportSavedCsvBtn');
if (exportSavedBtn) exportSavedBtn.addEventListener('click', () => exportActivePaneCsv('savedKeywordTabsContent'));

function exportActivePaneCsv(contentId) {
    const activePane = document.querySelector(`#${contentId} .tab-pane.active table`);
    if (!activePane) return;
    const rows = [];
    activePane.querySelectorAll('thead tr').forEach(tr => {
        rows.push([...tr.querySelectorAll('th')].map(th => `"${th.textContent.trim()}"`).join(','));
    });
    activePane.querySelectorAll('tbody tr').forEach(tr => {
        rows.push([...tr.querySelectorAll('td')].map(td => `"${td.textContent.trim().replace(/"/g,'""')}"`).join(','));
    });
    const blob = new Blob([rows.join('\n')], { type: 'text/csv' });
    const a    = document.createElement('a');
    a.href     = URL.createObjectURL(blob);
    a.download = `ranking_competitor_report_${Date.now()}.csv`;
    a.click();
}

// ─── Snippet modal — delegated ────────────────────────────────────────────────
['keywordTabsContent','savedKeywordTabsContent'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('click', function (e) {
        const cell = e.target.closest('.snippet-cell');
        if (!cell) return;
        document.getElementById('snippetModalTitle').textContent = cell.dataset.title || '—';
        document.getElementById('snippetModalUrl').innerHTML =
            `<a href="${cell.dataset.url}" target="_blank" class="small">${cell.dataset.url}</a>`;
        document.getElementById('snippetModalBody').textContent = cell.dataset.snippet || 'No description available.';
        new bootstrap.Modal(document.getElementById('snippetModal')).show();
    });
});
</script>
@endpush