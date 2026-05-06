@extends('layouts.app')

@section('title', 'Reporting Sheet')
@section('page_header', 'Reporting Sheet')
@section('page_icon', 'mdi mdi-format-list-bulleted-square')

@section('breadcrumb')
    <li class="breadcrumb-item active">SEO</li>
    <li class="breadcrumb-item active" aria-current="page">Reporting Sheet</li>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
<style>
    .save-status { font-size: 0.8rem; }

    #dashboardTable {
        width: 100%;
        border-collapse: collapse;
    }
    #dashboardTable th, #dashboardTable td {
        border: 1px solid #dee2e6;
        padding: 8px 12px;
        font-size: 0.875rem;
    }
    #dashboardTable thead th {
        background-color: #1e3a5f;
        color: #fff;
        text-align: center;
        font-weight: 600;
    }
    #dashboardTable tbody tr:nth-child(even) { background: #f8f9fa; }
    #dashboardTable tbody td:first-child { text-align: center; width: 60px; }
    #dashboardTable a { color: #1a56db; word-break: break-all; }
    #dashboardTable a:hover { text-decoration: underline; }

    .table-title-row td {
        background-color: #1e3a5f !important;
        color: #fff;
        text-align: center;
        font-weight: 700;
        font-size: 1rem;
        letter-spacing: 0.05em;
    }
</style>
@endpush

@section('content')

<div class="row g-4">

    {{-- ── FORM CARD ────────────────────────────────────────────────────── --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Reporting Sheet</h5>

                <form id="reportingForm" novalidate>
                    @csrf
                    <input type="hidden" name="created_by_user_id" value="{{ $created_by_user_id }}">
                    <input type="hidden" name="client_property_id"  value="{{ $client_property_id }}">

                    <div class="row g-4">
                        {{-- Client domain --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Our Client <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="ourclientInput" name="ourclient"
                                    placeholder="Enter the Client domain" value="{{ $domain }}" required>
                                <div class="invalid-feedback">Please enter the client domain.</div>
                            </div>
                        </div>

                        {{-- Drive Folder URL --}}
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">
                                    Google Spreadsheet URL <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="spreadsheetUrl" name="spreadsheet_url"
                                    placeholder="https://docs.google.com/spreadsheets/d/..." required>
                                <div class="invalid-feedback">Please enter a valid Google Spreadsheet URL.</div>
                                <div class="form-text text-muted mt-1">
                                    <i class="mdi mdi-information-outline"></i>
                                    The spreadsheet will be updated with the reporting tabs. Make sure you have edit access.
                                </div>
                            </div>
                        </div>

                        {{-- XML Sitemap Files --}}
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">
                                    Sitemap XML Files
                                    <span class="badge bg-secondary ms-1" style="font-size:0.7rem;">Optional</span>
                                </label>
                                <div id="xmlDropZone"
                                     class="border border-2 border-dashed rounded p-4 text-center"
                                     style="border-color:#ced4da!important; cursor:pointer; transition:background .2s;"
                                     ondragover="event.preventDefault(); this.style.background='#eef4ff';"
                                     ondragleave="this.style.background='';"
                                     ondrop="handleXmlDrop(event)">
                                    <i class="mdi mdi-file-xml-box fs-2 text-muted"></i>
                                    <p class="mb-1 text-muted">Drag &amp; drop <strong>.xml</strong> files here, or</p>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="$('#xmlFilesInput').click()">
                                        Browse files
                                    </button>
                                    <input type="file" id="xmlFilesInput" name="xml_files[]"
                                           accept=".xml,application/xml,text/xml"
                                           multiple class="d-none">
                                    <p class="form-text text-muted mb-0 mt-2">
                                        <i class="mdi mdi-information-outline"></i>
                                        Upload one or more sitemap XML files. They will be analysed for
                                        <strong>Duplicate H1</strong>, <strong>Multiple H1</strong>, and
                                        <strong>Missing Alt Text</strong> issues via Gemini AI.
                                    </p>
                                </div>

                                {{-- Selected file chips --}}
                                <div id="xmlFileList" class="d-flex flex-wrap gap-2 mt-2"></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3 mt-4">
                        <button type="submit" class="btn btn-primary" id="generateBtn">
                            <span id="generateBtnText">Generate Reporting Sheet</span>
                            <span id="generateBtnSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status"></span>
                        </button>
                        <button type="reset" class="btn btn-light" id="resetBtn">Reset</button>
                        <span id="saveStatus" class="save-status d-none text-muted"></span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── RESULTS CARD ─────────────────────────────────────────────────── --}}
    <div class="col-12" id="resultsSection" style="display:none;">
        <div class="card">
            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <h5 class="card-title mb-0">Dashboard</h5>
                    <a id="spreadsheetLink" href="#" target="_blank" class="btn btn-sm btn-success d-none">
                        <i class="mdi mdi-google-spreadsheet me-1"></i> Open in Google Sheets
                    </a>
                </div>

                <div class="table-responsive">
                    <table id="dashboardTable">
                        <thead>
                            <tr class="table-title-row">
                                <td colspan="3">Dashboard</td>
                            </tr>
                            <tr>
                                <th>S. No</th>
                                <th>Error</th>
                                <th>Doc File</th>
                            </tr>
                        </thead>
                        <tbody id="dashboardBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ── ERROR ALERT ──────────────────────────────────────────────────── --}}
    <div class="col-12" id="errorSection" style="display:none;">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="mdi mdi-alert-circle me-2"></i>
            <span id="errorMessage">An error occurred. Please try again.</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(function () {

    const form            = $('#reportingForm');
    const generateBtn     = $('#generateBtn');
    const btnText         = $('#generateBtnText');
    const btnSpinner      = $('#generateBtnSpinner');
    const resultsSection  = $('#resultsSection');
    const errorSection    = $('#errorSection');
    const errorMessage    = $('#errorMessage');
    const dashboardBody   = $('#dashboardBody');
    const spreadsheetLink = $('#spreadsheetLink');

    function setLoading(on) {
        generateBtn.prop('disabled', on);
        btnSpinner.toggleClass('d-none', !on);
        btnText.text(on ? 'Generating…' : 'Generate Reporting Sheet');
    }

    function showError(msg) {
        errorMessage.text(msg);
        errorSection.show();
        resultsSection.hide();
    }

    function hideError() { errorSection.hide(); }

    function renderDashboard(rows, spreadsheetUrl) {
        dashboardBody.empty();

        rows.forEach(function (row, idx) {
            const nameCell = $('<td>').text(row.name);
            let   docCell;

            if (row.url) {
                docCell = $('<td>').append(
                    $('<a>').attr({ href: row.url, target: '_blank' }).text(row.url)
                );
            } else {
                docCell = $('<td>').text(row.note || '—');
            }

            dashboardBody.append(
                $('<tr>').append($('<td>').text(idx + 1), nameCell, docCell)
            );
        });

        if (spreadsheetUrl) {
            spreadsheetLink.attr('href', spreadsheetUrl).removeClass('d-none');
        }

        resultsSection.show();
        $('html, body').animate({ scrollTop: resultsSection.offset().top - 80 }, 400);
    }

    form.on('submit', function (e) {
        e.preventDefault();
        hideError();

        if (!this.checkValidity()) {
            $(this).addClass('was-validated');
            return;
        }

        setLoading(true);

        // Use FormData so that file inputs are included in the request
        const fd = new FormData(this);

        // Attach any files added via drag-and-drop (stored in our custom array)
        if (window._xmlFiles && window._xmlFiles.length) {
            // Remove the default (possibly empty) file input entries first
            fd.delete('xml_files[]');
            window._xmlFiles.forEach(function (file) {
                fd.append('xml_files[]', file);
            });
        }

        $.ajax({
            url: '{{ route("reporting.sheet.form", [$created_by_user_id, $client_property_id]) }}',
            method: 'POST',
            data: fd,
            processData: false,   // required for FormData
            contentType: false,   // required for FormData
            success: function (res) {
                setLoading(false);
                if (res.success && res.rows) {
                    renderDashboard(res.rows, res.spreadsheet_url ?? null);
                } else {
                    showError(res.message ?? 'Unexpected response from server.');
                }
            },
            error: function (xhr) {
                setLoading(false);
                showError(xhr.responseJSON?.message ?? 'Server error. Please try again.');
            }
        });
    });

    $('#resetBtn').on('click', function () {
        hideError();
        resultsSection.hide();
        spreadsheetLink.addClass('d-none');
        dashboardBody.empty();
        form.removeClass('was-validated');
        // Clear XML file list
        window._xmlFiles = [];
        $('#xmlFileList').empty();
        $('#xmlFilesInput').val('');
        $('#xmlDropZone').css('background', '');
    });
});

/* ── XML file handling ──────────────────────────────────────────────────── */
window._xmlFiles = [];   // master list (merges browse + drag-drop)

function renderXmlChips() {
    const list = $('#xmlFileList');
    list.empty();
    window._xmlFiles.forEach(function (file, idx) {
        list.append(
            $('<span>')
                .addClass('badge bg-light text-dark border d-flex align-items-center gap-1 py-1 px-2')
                .css('font-size', '0.8rem')
                .append(
                    $('<i>').addClass('mdi mdi-file-xml-box text-primary'),
                    $('<span>').text(file.name),
                    $('<button>')
                        .attr('type', 'button')
                        .addClass('btn-close btn-close-sm ms-1')
                        .css('font-size', '0.6rem')
                        .on('click', function () {
                            window._xmlFiles.splice(idx, 1);
                            renderXmlChips();
                        })
                )
        );
    });
}

// Browse via file input
$('#xmlFilesInput').on('change', function () {
    Array.from(this.files).forEach(function (f) {
        if (!window._xmlFiles.find(x => x.name === f.name && x.size === f.size)) {
            window._xmlFiles.push(f);
        }
    });
    renderXmlChips();
});

// Drag & drop
function handleXmlDrop(event) {
    event.preventDefault();
    document.getElementById('xmlDropZone').style.background = '';
    const files = Array.from(event.dataTransfer.files).filter(f =>
        f.name.endsWith('.xml') || f.type === 'application/xml' || f.type === 'text/xml'
    );
    files.forEach(function (f) {
        if (!window._xmlFiles.find(x => x.name === f.name && x.size === f.size)) {
            window._xmlFiles.push(f);
        }
    });
    renderXmlChips();
}
</script>
@endpush 