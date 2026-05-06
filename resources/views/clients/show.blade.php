@extends('layouts.app')

@section('title', 'Client Properties')
@section('page_header', 'Client Properties')
@section('page_icon', 'mdi mdi-account-multiple')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{route('dashboard')}}">Clients</a></li>
<li class="breadcrumb-item active">Client Properties</li>
@endsection

@push('styles')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
@endpush

@section('content')
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <table id="scroll-horizontal" class="table nowrap align-middle" style="width:100%">
                            <thead>
                                <tr>
                                    <th scope="col">Property</th>
                                    <th scope="col">Domain</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if($client_data->isNotEmpty()){ ?>
                                    <?php foreach($client_data as $key => $client){ ?>
                                            <tr>
                                                @if($client->type == 'lms')
                                                <td>LMS</td>
                                                @elseif($client->type == 'website')
                                                <td>website</td>
                                                @elseif($client->type == 'landing')
                                                <td>website</td>
                                                @endif
                                                <td>{{$client->domain}}</td>
                                                <td>
                                                    <div class="dropdown d-inline-block">
                                                        <button class="btn btn-secondary btn-sm dropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ri-more-fill align-middle"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            
                                                            @if(in_array(Auth::user()->role->name, ['Admin']) || Auth::user()->team_id == 1)
                                                                <li class="dropdown-item">
                                                                    <a href="{{url('ranking-competitor-report/'.$client->user_id.'/'.$client->id)}}" class="btn btn-link nav-link" style="text-align:left; padding:0; border:none; background:none;">
                                                                        <i class="mdi_icon mdi mdi-pencil-box-multiple text-muted"></i> Ranking-Competitor Report
                                                                    </a> 
                                                                </li>
                                                                <li class="dropdown-item">
                                                                    <a href="{{url('core-web-vitals/'.$client->user_id.'/'.$client->id)}}" class="btn btn-link nav-link" style="text-align:left; padding:0; border:none; background:none;">
                                                                        <i class="mdi_icon mdi mdi-delete-circle-outline text-muted"></i> Core Web Vitals
                                                                    </a>
                                                                </li>
                                                                
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <a class="dropdown-item text-success" href="{{url('reporting-sheet/'.$client->user_id.'/'.$client->id)}}">
                                                                        Reporting Sheet
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item text-success" href="{{url('seo-dashboard/'.$client->user_id.'/'.$client->id)}}">
                                                                        SEO Dashboard
                                                                    </a>
                                                                </li>
                                                            @endif
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        
                                    <?php } ?>
                                <?php }else{ ?>
                                    <tr>
                                        <td colspan="4">No Record!!</td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div><!--end col-->
        </div><!--end row-->
@endsection