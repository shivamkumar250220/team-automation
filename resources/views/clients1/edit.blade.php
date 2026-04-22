@extends('layouts.app')

@section('title', 'Edit Client — ' . $client->name)
@section('page_header', 'Edit Client')
@section('page_icon', 'mdi mdi-account-edit')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Clients</a></li>
    <li class="breadcrumb-item active">Edit — {{ $client->name }}</li>
@endsection

@section('content')

<form action="{{ route('clients.update', $client->id) }}" method="POST" autocomplete="off">
    @csrf
    @method('PUT')

    <div class="row g-3">

        <div class="col-12">
            <div class="card mb-0">
                <div class="card-body">
                    <h6 class="card-title mb-1">Basic Information</h6>
                    <p class="card-subtitle mb-4">Client contact details</p>

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $client->name) }}">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone"
                                   class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $client->phone) }}">
                            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="mdi mdi-at"></i></span>
                                <input type="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $client->email) }}">
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">
                                Slug <span class="text-danger">*</span>
                                <small class="text-muted fw-normal ms-1">(same as used in LMS)</small>
                            </label>
                            <input type="text" name="slug"
                                   class="form-control @error('slug') is-invalid @enderror"
                                   value="{{ old('slug', $client->slug) }}">
                            @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card mb-0">
                <div class="card-body">
                    <h6 class="card-title mb-1">Business Details</h6>
                    <p class="card-subtitle mb-4">Industry, location and assignment</p>

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">Industry <span class="text-danger">*</span></label>
                            <select name="industry" class="form-select @error('industry') is-invalid @enderror">
                                @foreach(['dermatologist' => 'Dermatologist', 'ivf' => 'IVF', 'other' => 'Other'] as $val => $label)
                                    <option value="{{ $val }}" {{ old('industry', $client->industry) == $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('industry')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">City</label>
                            <select name="city" class="form-select @error('city') is-invalid @enderror">
                                <option value="">— Select city —</option>
                                @foreach(['Delhi','Mumbai','Bangalore','Chennai','Hyderabad','Pune','Other'] as $city)
                                    <option value="{{ $city }}" {{ old('city', $client->city) == $city ? 'selected' : '' }}>
                                        {{ $city }}
                                    </option>
                                @endforeach
                            </select>
                            @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Zip</label>
                            <input type="text" name="zip"
                                   class="form-control @error('zip') is-invalid @enderror"
                                   value="{{ old('zip', $client->zip) }}">
                            @error('zip')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">
                                Assigned User <span class="text-danger">*</span>
                                <small class="text-muted fw-normal ms-1">(team auto-assigned)</small>
                            </label>
                            <select name="user_id" class="form-select @error('user_id') is-invalid @enderror">
                                <option value="" disabled>Select user…</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user_id', $client->user_id) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                        @if($user->team) — {{ $user->team->name }} @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @if($client->team)
                        <div class="col-md-4">
                            <label class="form-label">Current Team</label>
                            <input type="text" class="form-control" value="{{ $client->team->name }}" readonly disabled>
                            <small class="text-muted">Auto-updated when user changes</small>
                        </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-0 h-100">
                <div class="card-body">
                    <h6 class="card-title mb-1">Status</h6>
                    <p class="card-subtitle mb-3">Account availability</p>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_active"
                                   value="active" {{ old('status', $client->status) == 'active' ? 'checked' : '' }}>
                            <label class="form-check-label fw-medium" for="status_active">Active</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="status_inactive"
                                   value="inactive" {{ old('status', $client->status) == 'inactive' ? 'checked' : '' }}>
                            <label class="form-check-label fw-medium" for="status_inactive">Inactive</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="mdi mdi-content-save me-1"></i> Update Client
                </button>
                <a href="{{ route('clients.index') }}" class="btn btn-light">
                    <i class="mdi mdi-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>

    </div>
</form>

@endsection