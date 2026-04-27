@extends('layouts.app')

@section('title', $client->name . ' — GMB Insights')
@section('page_header', $client->name)
@section('page_icon', 'mdi mdi-google-maps')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('gmb.insights.index') }}">GMB Insights</a></li>
    <li class="breadcrumb-item active">{{ $client->name }}</li>
@endsection

@section('content')

<div class="row g-3">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('gmb.insights.index') }}" class="btn btn-sm btn-light">
                <i class="mdi mdi-arrow-left me-1"></i> Back
            </a>
            <form method="POST" action="{{ route('gmb.insights.pull', $client) }}">
                @csrf
                <button class="btn btn-sm btn-success">
                    <i class="mdi mdi-download me-1"></i> Pull Now
                </button>
            </form>
        </div>
    </div>

    @forelse($locations as $location)
        <div class="col-12">
            <div class="card mb-0">
                <div class="card-body">

                    <h6 class="card-title mb-1">
                        {{ $location->location_name }}
                        @if($location->city)
                            <span class="text-muted fw-normal small ms-1">— {{ $location->city }}</span>
                        @endif
                    </h6>
                    <p class="card-subtitle mb-3">Last 6 months data</p>

                    <table class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Views</th>
                                <th>Calls</th>
                                <th>Directions</th>
                                <th>Top Queries</th>
                                <th>Pulled At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($location->insights as $insight)
                                <tr>
                                    <td>
                                        {{ DateTime::createFromFormat('!m', $insight->month)->format('F') }}
                                        {{ $insight->year }}
                                    </td>
                                    <td>{{ number_format($insight->views) }}</td>
                                    <td>{{ number_format($insight->calls) }}</td>
                                    <td>{{ number_format($insight->direction_requests) }}</td>
                                    <td>
                                        @forelse(array_slice($insight->search_queries ?? [], 0, 3) as $q)
                                            <span class="badge bg-secondary me-1">{{ $q['query'] }}</span>
                                        @empty
                                            <span class="text-muted">—</span>
                                        @endforelse
                                    </td>
                                    <td class="text-muted small">
                                        {{ $insight->pulled_at?->format('d M Y h:i A') ?? '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        No data yet — click Pull Now
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-warning">No locations found for this client.</div>
        </div>
    @endforelse

</div>

@endsection