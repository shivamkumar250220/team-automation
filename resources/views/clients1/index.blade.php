@extends('layouts.app')

@section('title', 'Client List')
@section('page_header', 'Client List')
@section('page_icon', 'mdi mdi-account-multiple')

@section('breadcrumb')
    <li class="breadcrumb-item active">Clients</li>
@endsection

@push('styles')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <a href="{{ route('clients.create') }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus me-1"></i> Add Client
                    </a>
                    <div id="dt-controls" class="d-flex align-items-center gap-2"></div>
                </div>

                <table id="clients-table" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Client</th>
                            <th>Industry</th>
                            <th>Team</th>
                            <th>City</th>
                            <th width="110">Status</th>
                            <th width="70" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clients as $index => $client)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $client->name }}</td>
                            <td>{{ $client->industry }}</td>
                            <td>{{ $client->team->name ?? '—' }}</td>
                            <td>{{ $client->city }}</td>
                            <td>{{ $client->status }}</td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                        <i class="mdi mdi-dots-horizontal"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('clients.show', $client->id) }}">
                                                View
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('clients.edit', $client->id) }}">
                                                Edit
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#"
                                            onclick="deleteClient({{ $client->id }}); return false;">
                                                Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>


@endsection
