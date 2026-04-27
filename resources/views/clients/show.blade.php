@extends('layouts.app')

@section('title', 'Client — ' . $client->name)
@section('page_header', $client->name)
@section('page_icon', 'mdi mdi-account')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clients.index') }}">Clients</a></li>
    <li class="breadcrumb-item active">{{ $client->name }}</li>
@endsection

@section('content')

<div class="row g-3">

    {{-- Tabs --}}
    <div class="col-12">
        <ul class="nav nav-tabs" id="clientTabs">
            @if($client->team_id === 2)
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#tab-locations">
                        <i class="mdi mdi-map-marker-multiple me-1"></i> GMB Locations
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#tab-credentials">
                        <i class="mdi mdi-key me-1"></i> API Credentials
                    </a>
                </li>
            @else
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#tab-info">
                        <i class="mdi mdi-information-outline me-1"></i> Info
                    </a>
                </li>
            @endif
        </ul>
    </div>

    <div class="col-12 tab-content" id="clientTabsContent">

        {{-- ── Tab: Info (non-GMB clients ke liye) ────────────────────── --}}
        @if($client->team_id !== 2)
        <div class="tab-pane fade show active" id="tab-info">
            <div class="card mb-0">
                <div class="card-body">
                    <h6 class="card-title mb-1">Basic Information</h6>
                    <p class="card-subtitle mb-4">Client contact details</p>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Name</label>
                            <p class="fw-medium mb-0">{{ $client->name }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Email</label>
                            <p class="fw-medium mb-0">{{ $client->email }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Phone</label>
                            <p class="fw-medium mb-0">{{ $client->phone }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Industry</label>
                            <p class="fw-medium mb-0">{{ ucfirst($client->industry) }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">City</label>
                            <p class="fw-medium mb-0">{{ $client->city ?? '—' }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Zip</label>
                            <p class="fw-medium mb-0">{{ $client->zip ?? '—' }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Team</label>
                            <p class="fw-medium mb-0">{{ $client->team->name ?? '—' }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Assigned User</label>
                            <p class="fw-medium mb-0">{{ $client->user->name ?? '—' }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Status</label>
                            <p class="mb-0">
                                <span class="badge bg-{{ $client->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($client->status) }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Slug</label>
                            <p class="fw-medium mb-0">{{ $client->slug }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small">Created By</label>
                            <p class="fw-medium mb-0">{{ $client->creator->name ?? '—' }}</p>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent d-flex gap-2">
                    <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-primary">
                        <i class="mdi mdi-pencil me-1"></i> Edit
                    </a>
                    <a href="{{ route('clients.index') }}" class="btn btn-sm btn-light">
                        <i class="mdi mdi-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>
        </div>
        @endif

        @if($client->team_id === 2)

        {{-- ── Tab: GMB Locations ───────────────────────────────────────── --}}
        <div class="tab-pane fade show active" id="tab-locations">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Add Location Form --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title mb-1">Add Location</h6>
                    <p class="card-subtitle mb-4">Add a new GMB listing for this client</p>

                    <form method="POST" action="{{ route('gmb.location.store', $client) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Location Name <span class="text-danger">*</span></label>
                                <input type="text" name="location_name"
                                       class="form-control @error('location_name') is-invalid @enderror"
                                       value="{{ old('location_name') }}"
                                       placeholder="e.g. Prime IVF - Gurugram">
                                @error('location_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">GBP Account ID <span class="text-danger">*</span></label>
                                <input type="text" name="gbp_account_id"
                                       class="form-control @error('gbp_account_id') is-invalid @enderror"
                                       value="{{ old('gbp_account_id') }}"
                                       placeholder="accounts/123456789">
                                @error('gbp_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">GBP Location ID <span class="text-danger">*</span></label>
                                <input type="text" name="gbp_location_id"
                                       class="form-control @error('gbp_location_id') is-invalid @enderror"
                                       value="{{ old('gbp_location_id') }}"
                                       placeholder="locations/987654321">
                                @error('gbp_location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City</label>
                                <input type="text" name="city"
                                       class="form-control @error('city') is-invalid @enderror"
                                       value="{{ old('city') }}"
                                       placeholder="Gurugram">
                                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="mdi mdi-plus me-1"></i> Add Location
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Locations List --}}
            <div class="card mb-0">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Location Name</th>
                                <th>GBP Account ID</th>
                                <th>GBP Location ID</th>
                                <th>City</th>
                                <th>Status</th>
                                <th class="text-center" width="70">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($gmbLocations as $index => $location)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $location->location_name }}</td>
                                    <td><code>{{ $location->gbp_account_id }}</code></td>
                                    <td><code>{{ $location->gbp_location_id }}</code></td>
                                    <td>{{ $location->city ?? '—' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $location->status === 'active' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($location->status) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                                <i class="mdi mdi-dots-horizontal"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="#"
                                                       data-bs-toggle="modal"
                                                       data-bs-target="#editLocation{{ $location->id }}">
                                                        Edit
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST"
                                                          action="{{ route('gmb.location.delete', [$client, $location]) }}">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item text-danger"
                                                                onclick="return confirm('Delete this location?')">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>

                                {{-- Edit Modal --}}
                                <div class="modal fade" id="editLocation{{ $location->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <form method="POST"
                                              action="{{ route('gmb.location.update', [$client, $location]) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Location</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Location Name <span class="text-danger">*</span></label>
                                                            <input type="text" name="location_name"
                                                                   class="form-control"
                                                                   value="{{ $location->location_name }}" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">GBP Account ID <span class="text-danger">*</span></label>
                                                            <input type="text" name="gbp_account_id"
                                                                   class="form-control"
                                                                   value="{{ $location->gbp_account_id }}" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label">GBP Location ID <span class="text-danger">*</span></label>
                                                            <input type="text" name="gbp_location_id"
                                                                   class="form-control"
                                                                   value="{{ $location->gbp_location_id }}" required>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label">City</label>
                                                            <input type="text" name="city"
                                                                   class="form-control"
                                                                   value="{{ $location->city }}">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label">Status</label>
                                                            <select name="status" class="form-select">
                                                                <option value="active" {{ $location->status === 'active' ? 'selected' : '' }}>Active</option>
                                                                <option value="inactive" {{ $location->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Update</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No locations added yet</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tab-credentials">
            <div class="card mb-0">
                <div class="card-body">
                    <h6 class="card-title mb-1">GBP API Credentials</h6>
                    <p class="card-subtitle mb-4">OAuth tokens for this client</p>

                    <div class="mb-4">
                        @if($gmbCredential && !$gmbCredential->isExpired())
                            <div class="alert alert-success d-flex align-items-center gap-2 mb-2">
                                <i class="mdi mdi-check-circle fs-5"></i>
                                <div>
                                    Google Account Connected
                                    <small class="d-block text-muted">
                                        Expires: {{ $gmbCredential->expires_at?->format('d M Y h:i A') }}
                                    </small>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('gmb.disconnect', $client) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="mdi mdi-link-off me-1"></i> Disconnect Google Account
                                </button>
                            </form>
                        @else
                            <div class="alert alert-warning d-flex align-items-center gap-2 mb-2">
                                <i class="mdi mdi-alert fs-5"></i>
                                <div>
                                    Google Account not connected yet
                                    @if($gmbCredential?->isExpired())
                                        <small class="d-block text-danger">Previous token expired</small>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('gmb.connect', $client) }}" class="btn btn-sm btn-primary">
                                <i class="mdi mdi-google me-1"></i> Connect Google Account
                            </a>
                        @endif
                    </div>

                    <hr class="mb-4">

                    {{-- Manual Token Form --}}
                    <h6 class="mb-3 text-muted small fw-semibold text-uppercase">Or Paste Tokens Manually</h6>

                    <form method="POST" action="{{ route('gmb.credential.save', $client) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Access Token <span class="text-danger">*</span></label>
                                <textarea name="access_token" rows="3"
                                          class="form-control @error('access_token') is-invalid @enderror"
                                          placeholder="Paste access token here">{{ old('access_token', $gmbCredential?->access_token) }}</textarea>
                                @error('access_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Refresh Token <span class="text-danger">*</span></label>
                                <textarea name="refresh_token" rows="3"
                                          class="form-control @error('refresh_token') is-invalid @enderror"
                                          placeholder="Paste refresh token here">{{ old('refresh_token', $gmbCredential?->refresh_token) }}</textarea>
                                @error('refresh_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Token Expires At</label>
                                <input type="datetime-local" name="expires_at"
                                       class="form-control @error('expires_at') is-invalid @enderror"
                                       value="{{ old('expires_at', $gmbCredential?->expires_at?->format('Y-m-d\TH:i')) }}">
                                @error('expires_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            @if($gmbCredential)
                                <div class="col-12">
                                    <p class="text-muted small mb-0">
                                        Last updated: {{ $gmbCredential->updated_at->diffForHumans() }}
                                        @if($gmbCredential->isExpired())
                                            <span class="badge bg-danger ms-2">Expired</span>
                                        @else
                                            <span class="badge bg-success ms-2">Valid</span>
                                        @endif
                                    </p>
                                </div>
                            @endif

                            <div class="col-12">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="mdi mdi-content-save me-1"></i> Save Credentials
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        @endif

    </div>
</div>

@endsection