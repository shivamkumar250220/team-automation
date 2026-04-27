@extends('layouts.app')

@section('title', 'GMB Dashboard')
@section('page_header', 'GMB Dashboard')
@section('page_icon', 'mdi mdi-google-maps')

@section('breadcrumb')
    <li class="breadcrumb-item active">GMB Dashboard</li>
@endsection

@push('styles')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <h6 class="mb-0 fw-semibold">Client List</h6>
                    <div id="dt-controls" class="d-flex align-items-center gap-2"></div>
                </div>

                <table id="gmb-clients-table" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Client</th>
                            <th>Industry</th>
                            <th>City</th>
                            <th width="110">Status</th>
                            <th width="100" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $index => $client)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="fw-medium">{{ $client->name }}</td>
                            <td>{{ ucfirst($client->industry) }}</td>
                            <td>{{ $client->city ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $client->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($client->status) }}
                                </span>
                            </td>
                             <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                        <i class="mdi mdi-dots-horizontal"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('gmb.clients.show', $client->id) }}">
                                                View
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('gmb.insights.index') }}">
                                                 Insights
                                            </a>
                                        </li>
                                        <a class="dropdown-item" href="{{ route('gmb.reviews.index', $client->id) }}">
                                            AI Drafts
                                        </a>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('gmb.citation.index', $client->id) }}">
                                                Citation Scan
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No GMB clients found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function () {
        $('#gmb-clients-table').DataTable({
            pageLength: 25,
            dom: 'rt<"d-flex align-items-center justify-content-between mt-3"ip>',
        });
    });
</script>
@endpush