@extends('layouts.app')

@section('title', 'Citation Scan — ' . $client->name)
@section('page_header', 'Citation Scan')
@section('page_icon', 'mdi mdi-map-marker-check-outline')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('gmb.clients.show', $client) }}">{{ $client->name }}</a></li>
    <li class="breadcrumb-item active">Citation Scan</li>
@endsection

@section('content')

<div class="row g-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('gmb.clients.show', $client) }}" class="btn btn-sm btn-light">
                <i class="mdi mdi-arrow-left me-1"></i> Back
            </a>
            <form method="POST" action="{{ route('gmb.citation.run', $client) }}">
                @csrf
                <button class="btn btn-sm btn-success fw-semibold">
                    <i class="mdi mdi-refresh me-1"></i> Run Citation Scan
                </button>
            </form>
        </div>
    </div>

    @if($gbpNap)
    <div class="col-12">
        <div class="card mb-0">
            <div class="card-body">
                <h6 class="card-title mb-3 fw-semibold">GBP Master NAP</h6>
                <div class="row g-2">
                    <div class="col-md-4">
                        <span class="text-muted small d-block">Name</span>
                        <span class="fw-medium">{{ $gbpNap['name'] ?? '—' }}</span>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted small d-block">Address</span>
                        <span class="fw-medium">{{ $gbpNap['address'] ?? '—' }}</span>
                    </div>
                    <div class="col-md-4">
                        <span class="text-muted small d-block">Phone</span>
                        <span class="fw-medium">{{ $gbpNap['phone'] ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @forelse($audits as $audit)
    <div class="col-12">
        <div class="card mb-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="card-title mb-0 fw-semibold">{{ $audit->directory }}</h6>
                    @if($audit->status === 'match')
                        <span class="badge bg-success">Match</span>
                    @elseif($audit->status === 'mismatch')
                        <span class="badge bg-danger">Mismatch</span>
                    @elseif($audit->status === 'corrected')
                        <span class="badge bg-info">Corrected</span>
                    @else
                        <span class="badge bg-secondary">Pending</span>
                    @endif
                </div>

                <table class="table table-sm w-100 mb-3">
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>GBP Value</th>
                            <th>Directory Value</th>
                            <th width="100">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-muted small">Name</td>
                            <td class="small">{{ $audit->gbp_name ?? '—' }}</td>
                            <td class="small">{{ $audit->found_name ?? '—' }}</td>
                            <td>
                                @if($audit->gbp_name && $audit->found_name)
                                    @if(strtolower(trim($audit->gbp_name)) === strtolower(trim($audit->found_name)))
                                        <span class="badge bg-success">OK</span>
                                    @else
                                        <span class="badge bg-danger">Mismatch</span>
                                    @endif
                                @else
                                    <span class="badge bg-secondary">—</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Address</td>
                            <td class="small">{{ $audit->gbp_address ?? '—' }}</td>
                            <td class="small">{{ $audit->found_address ?? '—' }}</td>
                            <td>
                                @if($audit->gbp_address && $audit->found_address)
                                    @if(strtolower(trim($audit->gbp_address)) === strtolower(trim($audit->found_address)))
                                        <span class="badge bg-success">OK</span>
                                    @else
                                        <span class="badge bg-danger">Mismatch</span>
                                    @endif
                                @else
                                    <span class="badge bg-secondary">—</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Phone</td>
                            <td class="small">{{ $audit->gbp_phone ?? '—' }}</td>
                            <td class="small">{{ $audit->found_phone ?? '—' }}</td>
                            <td>
                                @if($audit->gbp_phone && $audit->found_phone)
                                    @if(preg_replace('/\D/', '', $audit->gbp_phone) === preg_replace('/\D/', '', $audit->found_phone))
                                        <span class="badge bg-success">OK</span>
                                    @else
                                        <span class="badge bg-danger">Mismatch</span>
                                    @endif
                                @else
                                    <span class="badge bg-secondary">—</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div class="d-flex align-items-center gap-2 mb-3">
                    @if($audit->status === 'mismatch')
                    <form method="POST" action="{{ route('gmb.citation.mark-corrected', $audit) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success fw-semibold">
                            <i class="mdi mdi-check me-1"></i> Mark as Corrected
                        </button>
                    </form>
                    @endif
                    <button class="btn btn-sm btn-primary fw-semibold"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#manual-{{ $audit->id }}">
                        <i class="mdi mdi-pencil me-1"></i> Manual Entry
                    </button>
                </div>

                <div class="collapse" id="manual-{{ $audit->id }}">
                    <div class="p-3 bg-light rounded border">
                        <p class="small fw-semibold mb-2">Enter directory values manually:</p>
                        <form method="POST" action="{{ route('gmb.citation.manual-update', $audit) }}">
                            @csrf
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Name</label>
                                    <input type="text"
                                           name="found_name"
                                           class="form-control form-control-sm"
                                           value="{{ $audit->found_name }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Address</label>
                                    <input type="text"
                                           name="found_address"
                                           class="form-control form-control-sm"
                                           value="{{ $audit->found_address }}">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Phone</label>
                                    <input type="text"
                                           name="found_phone"
                                           class="form-control form-control-sm"
                                           value="{{ $audit->found_phone }}">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary fw-semibold">
                                <i class="mdi mdi-content-save me-1"></i> Save & Compare
                            </button>
                        </form>
                    </div>
                </div>

                <p class="text-muted small mb-0 mt-2">Last scanned: {{ $audit->scanned_at?->format('d M Y, h:i A') ?? '—' }}</p>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-warning">No citation audits found. Click Run Citation Scan to start.</div>
    </div>
    @endforelse
</div>

@endsection