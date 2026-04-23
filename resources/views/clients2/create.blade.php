@extends('layouts.app')

@section('title', 'Add Client')
@section('page_header', 'Add Client')
@section('page_icon', 'mdi mdi-account-multiple')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{route('clients')}}">Clients</a></li>
    <li class="breadcrumb-item active">Add Client</li>
@endsection

@push('styles')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet">
@endpush

@section("content")

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="live-preview">
                            <form class="row g-3" method="post" action="{{url('add-client')}}">
                                @csrf
                                <div class="col-md-4">
                                    <label for="validationDefault01" class="form-label">Name*</label>
                                    <input type="text" name="name" class="form-control" id="validationDefault01" value="" required="">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault02" class="form-label">Phone*</label>
                                    <input type="text" name="phone" class="form-control" id="validationDefault02" value="" required="">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefaultUsername" class="form-label">Email*</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="inputGroupPrepend2">@</span>
                                        <input type="text" name="email" class="form-control" id="validationDefaultUsername" aria-describedby="inputGroupPrepend2" required="">
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="validationDefault04" class="form-label">Industry*</label>
                                    <select class="form-select" name="industry" id="validationDefault04" required="">
                                        <option selected="" disabled="" value="">Choose...</option>
                                        <?php foreach($industry as $key => $indus){ ?>
                                        <option value="{{$indus->id}}">{{$indus->name}}</option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault05" class="form-label">City*</label>
                                    <select class="form-select" name="city" id="validationDefault05" required="">
                                        <option selected="" disabled="" value="">Choose...</option>
                                        <option value="delhi">Delhi</option>
                                        <option value="gurugram">Gurugram</option>
                                        <option value="noida">Noida</option>
                                        <option value="mumbai">Mumbai</option>
                                        <option value="bangalore">Bangalore</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault05" class="form-label">Zip*</label>
                                    <input type="text" name="zip" class="form-control" id="validationDefault05" required="">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault02" class="form-label">Password*</label>
                                    <input type="password" id="password" name="password" class="form-control" value="" required="">
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault05" class="form-label">Status</label>
                                    <div class="form-check">
                                        <input type="radio" value="active" class="form-check-input" checked="checked" id="validationFormCheck2" name="status">
                                        <label class="form-check-label" for="validationFormCheck2">Active</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" value="inactive" class="form-check-input" id="validationFormCheck3" name="status">
                                        <label class="form-check-label" for="validationFormCheck3">InActive</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="validationDefault05" class="form-label">Type</label>
                                    <div class="form-check">
                                        <input type="radio" value="Admin" class="form-check-input" checked="checked" id="validationFormCheck2" name="type">
                                        <label class="form-check-label" for="validationFormCheck2">Admin</label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" value="SA" class="form-check-input" id="validationFormCheck3" name="type">
                                        <label class="form-check-label" for="validationFormCheck3">Super Admin</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary" type="submit">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div> <!-- end col -->
        </div>
@endsection