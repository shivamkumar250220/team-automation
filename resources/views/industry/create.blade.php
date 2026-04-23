@extends('layouts.app')

@section('title', 'Add Industry')
@section('page_header', 'Add Industry')
@section('page_icon', 'mdi mdi-account-plus')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('industry.index') }}">Industry</a></li>
    <li class="breadcrumb-item active">Add Industry</li>
@endsection

@section('content')

<form action="{{ route('industry.store') }}" method="POST" autocomplete="off">
    @csrf

    <div class="row g-3">

        <div class="col-12">
            <div class="card mb-0">
                <div class="card-body">
                    <h6 class="card-title mb-1">Basic Information</h6>
                    <p class="card-subtitle mb-4">Industry contact details</p>

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" placeholder="e.g. Delhi Laser Clinic">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="mdi mdi-check me-1"></i> Create Industry
                </button>
                <a href="{{ route('industry.index') }}" class="btn btn-light">
                    <i class="mdi mdi-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

    </div>
</form>

@endsection