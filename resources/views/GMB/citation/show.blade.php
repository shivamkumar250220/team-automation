@extends('layouts.app')

@section('title', 'Citation Audit — ' . $client->name)
@section('page_header', 'Citation Audit')
@section('page_icon', 'mdi mdi-map-marker-check-outline')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('gmb.citations.index') }}">Citation Audit</a>
    </li>
    <li class="breadcrumb-item active">{{ $client->name }}</li>
@endsection

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <a href="{{ route('gmb.citations.index') }}" class="btn btn-sm btn-light me-2">
            <i class="mdi mdi-arrow-left me-1"></i>Back
        </a>
        <span class="text-muted small">
            {{ DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}
        </span>
    </div>

    <div class="d-flex gap-2 align-items-center flex-wrap">
        <form method="GET" class="d-flex gap-2">
            <select name="month" class="form-select form-select-sm" style="width:120px" onchange="this.form.submit()">
                @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" @selected($m == $month)>
                        {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                    </option>
                @endforeach
            </select>
            <select name="year" class="form-select form-select-sm" style="width:85px" onchange="this.form.submit()">
                @foreach([now()->year, now()->year - 1] as $y)
                    <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                @endforeach
            </select>
        </form>

        <form method="POST" action="{{ route('gmb.citations.run', $client) }}">
            @csrf
            <button class="btn btn-sm btn-primary">
                <i class="mdi mdi-magnify me-1"></i>Run Full Scan
            </button>
        </form>
    </div>
</div>

@forelse($locations as $location)
    @php
        $audits     = $location->citationAudits;
        $mismatches = $audits->where('has_mismatch', true)->where('is_fixed', false)->count();
    @endphp

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="mdi mdi-map-marker-outline me-1 text-muted"></i>
                <span class="fw-semibold">{{ $location->location_name ?? $location->name }}</span>
                @if($location->city ?? null)
                    <span class="text-muted small ms-2">— {{ $location->city }}</span>
                @endif
            </div>
            @if($mismatches > 0)
                <span class="badge bg-danger">
                    <i class="mdi mdi-alert me-1"></i>{{ $mismatches }} issue{{ $mismatches > 1 ? 's' : '' }} need fixing
                </span>
            @elseif($audits->isNotEmpty())
                <span class="badge bg-success"><i class="mdi mdi-check me-1"></i>All consistent</span>
            @else
                <span class="badge bg-secondary">Not scanned yet</span>
            @endif
        </div>

        <div class="card-body p-0">
            @if($audits->isEmpty())
                <div class="p-4 text-center text-muted">
                    <i class="mdi mdi-magnify fs-3 d-block mb-2"></i>
                    No audit data yet. Run a scan to check this location's citations.
                </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="110">Directory</th>
                            <th>GBP Data</th>
                            <th>Found on Directory</th>
                            <th width="130">Result</th>
                            <th width="110">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($audits->sortBy('directory') as $audit)
                        <tr class="{{ $audit->has_mismatch && !$audit->is_fixed ? 'table-danger' : ($audit->is_fixed ? 'table-info' : '') }}">

                            <td>
                                @if($audit->directory_url)
                                    <a href="{{ $audit->directory_url }}" target="_blank"
                                       class="fw-semibold text-decoration-none d-flex align-items-center gap-1">
                                        {{ $audit->directoryLabel() }}
                                        <i class="mdi mdi-open-in-new small"></i>
                                    </a>
                                @else
                                    <span class="fw-semibold">{{ $audit->directoryLabel() }}</span>
                                @endif
                            </td>

                            <td>
                                <div class="small">
                                    <div class="mb-1"><span class="text-muted">Name:</span> <span class="ms-1">{{ $audit->gbp_name ?? '—' }}</span></div>
                                    <div class="mb-1"><span class="text-muted">Address:</span> <span class="ms-1">{{ $audit->gbp_address ?? '—' }}</span></div>
                                    <div><span class="text-muted">Phone:</span> <span class="ms-1">{{ $audit->gbp_phone ?? '—' }}</span></div>
                                </div>
                            </td>

                            <td>
                                @if($audit->not_found)
                                    <span class="badge bg-secondary py-2 px-3">
                                        <i class="mdi mdi-map-marker-off me-1"></i>Listing not found
                                    </span>
                                @else
                                    <div class="small">
                                        <div class="mb-1 {{ !$audit->name_match ? 'text-danger fw-semibold' : '' }}">
                                            <span class="text-muted">Name:</span>
                                            <span class="ms-1">{{ $audit->found_name ?? '—' }}</span>
                                            @if(!$audit->name_match)<i class="mdi mdi-alert-circle text-danger ms-1"></i>@endif
                                        </div>
                                        <div class="mb-1 {{ !$audit->address_match ? 'text-danger fw-semibold' : '' }}">
                                            <span class="text-muted">Address:</span>
                                            <span class="ms-1">{{ $audit->found_address ?: '—' }}</span>
                                            @if(!$audit->address_match)<i class="mdi mdi-alert-circle text-danger ms-1"></i>@endif
                                        </div>
                                        <div class="{{ !$audit->phone_match ? 'text-danger fw-semibold' : '' }}">
                                            <span class="text-muted">Phone:</span>
                                            <span class="ms-1">{{ $audit->found_phone ?: '—' }}</span>
                                            @if(!$audit->phone_match)<i class="mdi mdi-alert-circle text-danger ms-1"></i>@endif
                                        </div>
                                    </div>
                                @endif
                            </td>

                            <td>
                                @if($audit->is_fixed)
                                    <span class="badge bg-info py-2 px-3"><i class="mdi mdi-check me-1"></i>Fixed</span>
                                @elseif($audit->not_found)
                                    <span class="badge bg-secondary py-2 px-3">Not Listed</span>
                                @elseif($audit->has_mismatch)
                                    <span class="badge bg-danger py-2 px-3">
                                        <i class="mdi mdi-alert me-1"></i>{{ implode(', ', $audit->mismatchSummary()) }}
                                    </span>
                                @else
                                    <span class="badge bg-success py-2 px-3"><i class="mdi mdi-check me-1"></i>Consistent</span>
                                @endif
                            </td>

                            <td>
                                @if($audit->has_mismatch && !$audit->is_fixed)
                                    <form method="POST" action="{{ route('gmb.citations.fix', $audit) }}">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-sm btn-outline-success">
                                            <i class="mdi mdi-check me-1"></i>Mark Fixed
                                        </button>
                                    </form>
                                @elseif($audit->not_found)
                                    <a href="{{ 'https://www.google.com/search?q=' . urlencode($client->name . ' ' . $audit->directoryLabel()) }}"
                                       target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="mdi mdi-plus me-1"></i>Add Listing
                                    </a>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
@empty
    <div class="card">
        <div class="card-body text-center text-muted py-4">
            <i class="mdi mdi-map-marker-off fs-3 d-block mb-2"></i>
            No active locations found for this client.
        </div>
    </div>
@endforelse

@endsection