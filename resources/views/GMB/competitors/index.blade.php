@extends('layouts.app')

@section('title', 'Competitor Tracker — ' . $client->name)
@section('page_header', 'Competitor Tracker')
@section('page_icon', 'mdi mdi-chart-bar')

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('gmb.clients.show', $client) }}">{{ $client->name }}</a>
    </li>
    <li class="breadcrumb-item active">Competitor Tracker</li>
@endsection

@section('content')
<div class="row g-3">

    {{-- Header --}}
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('gmb.clients.show', $client) }}" class="btn btn-sm btn-light">
                <i class="mdi mdi-arrow-left me-1"></i> Back
            </a>
            <form method="POST" action="{{ route('gmb.competitors.pull', $client) }}">
                @csrf
                <button class="btn btn-sm btn-success fw-semibold">
                    <i class="mdi mdi-refresh me-1"></i> Pull Now
                </button>
            </form>
        </div>
    </div>

    {{-- Comparison Table --}}
    <div class="col-12">
        <div class="card mb-0">
            <div class="card-body">
                <h6 class="card-title fw-semibold mb-1">Rating Comparison</h6>
                <p class="text-muted small mb-3">
                    Auto-fetched top 5 competitors based on
                    <strong>{{ $client->industry }}</strong> in
                    <strong>{{ $client->city }}</strong>
                </p>
                <table class="table table-sm w-100">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th class="text-center">Type</th>
                            <th class="text-center">Rating</th>
                            <th class="text-center">Reviews</th>
                            <th class="text-center">Photos</th>
                            <th class="text-center">Rating Gap</th>
                            <th class="text-center">Review Gap</th>
                            <th class="text-center">Last Pulled</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Client own row --}}
                        <tr class="table-light fw-semibold">
                            <td>{{ $client->name }} <span class="badge bg-primary ms-1">You</span></td>
                            <td class="text-center">—</td>
                            <td class="text-center">{{ $clientStats['rating'] ?? '—' }}</td>
                            <td class="text-center">{{ $clientStats['review_count'] ?? '—' }}</td>
                            <td class="text-center">—</td>
                            <td class="text-center">—</td>
                            <td class="text-center">—</td>
                            <td class="text-center">—</td>
                            <td></td>
                        </tr>

                        @forelse($competitors as $competitor)
                        @php
                            $stat      = $competitor->latestStat;
                            $ratingGap = ($clientStats['rating'] && $stat?->rating)
                                ? round($clientStats['rating'] - $stat->rating, 1)
                                : null;
                            $reviewGap = ($clientStats['review_count'] && $stat?->review_count)
                                ? $clientStats['review_count'] - $stat->review_count
                                : null;
                        @endphp
                        <tr>
                            <td>{{ $competitor->name }}</td>
                            <td class="text-center">
                                @if($competitor->is_manual)
                                    <span class="badge bg-secondary">Manual</span>
                                @else
                                    <span class="badge bg-info">Auto</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $stat?->rating ?? '—' }}</td>
                            <td class="text-center">{{ $stat?->review_count ?? '—' }}</td>
                            <td class="text-center">{{ $stat?->photo_count ?? '—' }}</td>
                            <td class="text-center">
                                @if($ratingGap !== null)
                                    <span class="badge {{ $ratingGap >= 0 ? 'bg-success' : 'bg-danger' }}">
                                        {{ $ratingGap >= 0 ? '+' : '' }}{{ $ratingGap }}
                                    </span>
                                @else —
                                @endif
                            </td>
                            <td class="text-center">
                                @if($reviewGap !== null)
                                    <span class="badge {{ $reviewGap >= 0 ? 'bg-success' : 'bg-danger' }}">
                                        {{ $reviewGap >= 0 ? '+' : '' }}{{ $reviewGap }}
                                    </span>
                                @else —
                                @endif
                            </td>
                            <td class="text-center small text-muted">
                                {{ $stat?->pulled_at?->format('d M Y') ?? '—' }}
                            </td>
                            <td class="text-center">
                                <form method="POST"
                                      action="{{ route('gmb.competitors.destroy', [$client, $competitor]) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-light text-danger">
                                        <i class="mdi mdi-delete-outline"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-muted small text-center">
                                No competitor data yet. Click Pull Now to fetch.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Manual Add (optional) --}}
    <div class="col-12">
        <div class="card mb-0">
            <div class="card-body">
                <h6 class="card-title fw-semibold mb-1">Add Competitor Manually</h6>
                <p class="text-muted small mb-3">
                    Koi specific competitor track karna ho jo auto list mein nahi aaya.
                </p>
                <form method="POST" action="{{ route('gmb.competitors.store', $client) }}">
                    @csrf
                    <input type="hidden" name="is_manual" value="1">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label small text-muted">Competitor Name</label>
                            <input type="text" name="name" class="form-control form-control-sm"
                                   placeholder="e.g. Apollo Clinic Gurgaon" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small text-muted">Google Place ID</label>
                            <input type="text" name="place_id" class="form-control form-control-sm"
                                   placeholder="e.g. ChIJN1t_tDeuEmsRUsoyG83frY4" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-sm btn-primary fw-semibold w-100">
                                <i class="mdi mdi-plus me-1"></i> Add
                            </button>
                        </div>
                    </div>
                    <p class="text-muted small mt-2 mb-0">
                        Place ID —
                        <a href="https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder"
                           target="_blank">Place ID Finder</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection