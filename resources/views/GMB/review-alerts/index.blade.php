@extends('layouts.app')

@section('title', 'Review Alerts — ' . $client->name)
@section('page_header', 'Review Alerts')
@section('page_icon', 'mdi mdi-bell-alert-outline')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('gmb.clients.show', $client) }}">{{ $client->name }}</a>
    </li>
    <li class="breadcrumb-item active">Review Alerts</li>
@endsection

@section('content')

<div class="row g-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('gmb.clients.show', $client) }}" class="btn btn-sm btn-light">
                <i class="mdi mdi-arrow-left me-1"></i> Back
            </a>
            <form method="POST" action="{{ route('gmb.review-alerts.run', $client) }}">
                @csrf
                <button class="btn btn-sm btn-danger fw-semibold">
                    <i class="mdi mdi-refresh me-1"></i> Check Now
                </button>
            </form>
        </div>
    </div>

    @forelse($alerts as $alert)
    <div class="col-12">
        <div class="card mb-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h6 class="card-title mb-0 fw-semibold">{{ $alert->reviewer_name ?? 'Anonymous' }}</h6>
                        <span class="text-muted small">{{ $alert->alert_sent_at?->format('d M Y, h:i A') }}</span>
                    </div>
                    <span class="badge bg-danger">
                        {{ match($alert->rating) {
                            'ONE'   => '1 Star',
                            'TWO'   => '2 Stars',
                            default => $alert->rating
                        } }}
                    </span>
                </div>

                <table class="table table-sm w-100 mb-3">
                    <thead>
                        <tr>
                            <th width="150">Field</th>
                            <th>Content</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-muted small">Review</td>
                            <td class="small">{{ $alert->comment ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted small">Draft Reply</td>
                            <td class="small">{{ $alert->draft_reply ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>

                <p class="text-muted small mb-0">
                    Alert sent: {{ $alert->alert_sent_at?->format('d M Y, h:i A') ?? '—' }}
                </p>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="alert alert-warning">
            No negative review alerts yet. Click Check Now to scan.
        </div>
    </div>
    @endforelse
</div>

@endsection