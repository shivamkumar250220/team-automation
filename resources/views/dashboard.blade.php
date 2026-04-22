@extends('layouts.app')

@section('title', 'Clients')
@section('page_header', 'Clients')
@section('page_icon', 'mdi mdi-account-multiple')

@section('breadcrumb')
    <li class="breadcrumb-item active">Clients</li>
@endsection

@push('styles')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
@endpush

@section('content')

<div class="card">
    <div class="card-body">
        <h4>Welcome, {{ $user->name }}</h4>
        <p><strong>Role:</strong> {{ $user->role->name ?? 'N/A' }}</p>
        @if(Auth::user()->role_id == 2)
            <p><strong>Team:</strong> {{ $user->team->name ?? 'N/A' }}</p>

        @elseif(Auth::user()->role_id == 3)
            <p><strong>Team:</strong> {{ $user->team->name ?? 'N/A' }}</p>
            <p><strong>Manager:</strong> {{ $user->manager->name ?? 'N/A' }}</p>

        @endif
    </div>
</div>

<div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <table id="scroll-horizontal" class="table nowrap align-middle" style="width:100%">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Client</th>
                                    <th scope="col">Industry</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>

                                <?php if($clients->isNotEmpty()){ ?>
                                    <?php foreach($clients as $key => $client){ ?>
                                        <tr>
                                            <td class="fw-medium">{{$key+1}}</td>
                                            <td>{{$client->name}}</td>
                                            <td>{{$client->industry}}</td>
                                            <td>{{$client->status}}</td>
                                            <td>
                                                <div class="dropdown d-inline-block">
                                                    <button class="btn btn-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="ri-more-fill align-middle"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a href="{{url('view-client/'.$client->id)}}" class="dropdown-item"><i class="mdi_icon mdi mdi-eye-circle text-muted"></i> Properties</a></li>
                                                        <!-- <li><a href="{{url('gauth/'.$client->id)}}" class="dropdown-item"><i class="mdi_icon mdi mdi-google text-muted"></i> Google Auth</a></li> -->
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php }else{ ?>
                                    <tr>
                                        <td colspan="5">No Record!!</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div><!--end col-->
        </div><!--end row-->
@endsection