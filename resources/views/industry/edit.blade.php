@extends('layouts.app')

@section('title', 'Edit Industry — ' . $industry->name)
@section('page_header', 'Edit Industry')
@section('page_icon', 'mdi mdi-account-edit')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('industry.index') }}">Industry List</a></li>
    <li class="breadcrumb-item active">Edit — {{ $industry->name }}</li>
@endsection

@section('content')

<form action="{{ route('industry.update', $industry->id) }}" method="POST" autocomplete="off">
    @csrf
    @method('PUT')

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
                                   value="{{ old('name', $industry->name) }}">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="mdi mdi-content-save me-1"></i> Update Industry
                </button>
                <a href="{{ route('industry.index') }}" class="btn btn-light">
                    <i class="mdi mdi-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

    </div>
</form>

@endsection