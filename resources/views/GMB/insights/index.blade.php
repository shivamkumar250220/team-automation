@extends('layouts.app')

@section('title', 'GMB Insights')
@section('page_header', 'GMB Insights')
@section('page_icon', 'mdi mdi-google-maps')

@section('breadcrumb')
    <li class="breadcrumb-item active">GMB Insights</li>
@endsection

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <form method="GET" class="d-flex gap-2">
                        <select name="month" class="form-select form-select-sm">
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ $m == $month ? 'selected' : '' }}>
                                    {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                                </option>
                            @endforeach
                        </select>
                        <select name="year" class="form-select form-select-sm">
                            @foreach([now()->year, now()->year - 1] as $y)
                                <option value="{{ $y }}" {{ $y == $year ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-sm btn-primary">Filter</button>
                    </form>
                </div>

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <table class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Client</th>
                            <th>Location</th>
                            <th>Views</th>
                            <th>Calls</th>
                            <th>Directions</th>
                            <th>Last Pulled</th>
                            <th width="70" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $index => $client)
                            @forelse($client->gmbLocations as $location)
                                @php $insight = $location->insights->first(); @endphp
                                <tr>
                                    @if($loop->first)
                                        <td rowspan="{{ $client->gmbLocations->count() }}" class="align-middle">
                                            {{ $index + 1 }}
                                        </td>
                                        <td rowspan="{{ $client->gmbLocations->count() }}" class="align-middle fw-medium">
                                            {{ $client->name }}
                                        </td>
                                    @endif
                                    <td>{{ $location->location_name }}</td>
                                    <td>{{ $insight->views ?? '—' }}</td>
                                    <td>{{ $insight->calls ?? '—' }}</td>
                                    <td>{{ $insight->direction_requests ?? '—' }}</td>
                                    <td>{{ $insight ? $insight->pulled_at->diffForHumans() : '—' }}</td>
                                    @if($loop->first)
                                        <td rowspan="{{ $client->gmbLocations->count() }}" class="align-middle text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                                    <i class="mdi mdi-dots-horizontal"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('gmb.insights.show', $client) }}">
                                                            View Detail
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="{{ route('gmb.insights.pull', $client) }}">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item">Pull Now</button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="fw-medium">{{ $client->name }}</td>
                                    <td colspan="6" class="text-muted">No locations added</td>
                                </tr>
                            @endforelse
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No GMB clients found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

@endsection