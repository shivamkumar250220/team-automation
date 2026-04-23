@extends('layouts.app')

@section('title', 'Dashboard')

@section('page_header', 'Dashboard')
@section('page_icon', 'mdi mdi-view-dashboard-outline')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Clients</li>
@endsection

@push('styles')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
@endpush

@section("content")
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">


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
                                            <a href="{{url('view-client/'.$client->id)}}" class="dropdown-item">
                                                <i class="mdi_icon mdi mdi-eye-circle text-muted"></i> Properties
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