@extends('layouts.app')

@section('title', 'Form Elements')
@section('page_header', 'Form Elements')
@section('page_icon', 'mdi mdi-format-list-bulleted-square')

@section('breadcrumb')
    <li class="breadcrumb-item active">Forms</li>
    <li class="breadcrumb-item active" aria-current="page">Basic Forms</li>
@endsection

@section('content')

<div class="row g-4">

    {{-- Default Form --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Default Form</h5>
                <p class="card-subtitle">Standard vertical form layout</p>

                <form>
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" placeholder="Enter username">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="mdi mdi-email-outline"></i></span>
                            <input type="email" class="form-control" placeholder="Enter email">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" placeholder="Enter password" id="password1">
                            <button class="btn btn-light btn-icon" type="button" onclick="togglePwd('password1')">
                                <i class="mdi mdi-eye-outline"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" placeholder="Confirm password">
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">Remember me</label>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Submit</button>
                        <button type="reset" class="btn btn-light">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Horizontal Form --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title">Horizontal Form</h5>
                <p class="card-subtitle">Labels beside fields</p>

                <form>
                    @csrf
                    <div class="row mb-3 align-items-center">
                        <label class="col-sm-4 col-form-label form-label">Email</label>
                        <div class="col-sm-8">
                            <input type="email" class="form-control" placeholder="Enter email">
                        </div>
                    </div>
                    <div class="row mb-3 align-items-center">
                        <label class="col-sm-4 col-form-label form-label">Mobile</label>
                        <div class="col-sm-8">
                            <input type="tel" class="form-control" placeholder="Enter mobile">
                        </div>
                    </div>
                    <div class="row mb-3 align-items-center">
                        <label class="col-sm-4 col-form-label form-label">Password</label>
                        <div class="col-sm-8">
                            <input type="password" class="form-control" placeholder="Password">
                        </div>
                    </div>
                    <div class="row mb-3 align-items-center">
                        <label class="col-sm-4 col-form-label form-label">Role</label>
                        <div class="col-sm-8">
                            <select class="form-select">
                                <option value="">Select role</option>
                                <option>Admin</option>
                                <option>Manager</option>
                                <option>User</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-8 offset-sm-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="agreedH">
                                <label class="form-check-label" for="agreedH">I agree to terms</label>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-8 offset-sm-4 d-flex gap-2">
                            <button class="btn btn-primary" type="submit">Submit</button>
                            <button class="btn btn-light" type="reset">Reset</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Advanced Elements --}}
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Advanced Form Elements</h5>
                <p class="card-subtitle">Various input types and components</p>

                <div class="row g-4">
                    <div class="col-md-6">

                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" placeholder="Enter full name">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Gender</label>
                            <select class="form-select">
                                <option value="">Select gender</option>
                                <option>Male</option>
                                <option>Female</option>
                                <option>Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control">
                        </div>

                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" placeholder="Enter city">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Profile Picture</label>
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Upload image" id="fileInfo" readonly>
                                <label class="btn btn-primary mb-0" for="fileInput">Browse</label>
                                <input type="file" id="fileInput" class="d-none" onchange="document.getElementById('fileInfo').value=this.files[0]?.name||''">
                            </div>
                        </div>

                    </div>
                    <div class="col-md-6">

                        <div class="form-group">
                            <label class="form-label">About</label>
                            <textarea class="form-control" rows="4" placeholder="Write something about yourself..."></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Notification Preference</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="notify" id="notifyEmail" value="email" checked>
                                    <label class="form-check-label" for="notifyEmail">Email</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="notify" id="notifySMS" value="sms">
                                    <label class="form-check-label" for="notifySMS">SMS</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="notify" id="notifyNone" value="none">
                                    <label class="form-check-label" for="notifyNone">None</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Interests</label>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach(['Technology', 'Design', 'Marketing', 'Finance'] as $interest)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="int_{{ $interest }}">
                                    <label class="form-check-label" for="int_{{ $interest }}">{{ $interest }}</label>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Priority Level</label>
                            <input type="range" class="form-range" min="0" max="10" value="5">
                            <div class="d-flex justify-content-between small text-muted">
                                <span>Low</span><span>Medium</span><span>High</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label d-flex justify-content-between">
                                <span>Email Notifications</span>
                                <span class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" checked>
                                </span>
                            </label>
                        </div>

                    </div>
                </div>

                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-primary"><i class="mdi mdi-check me-1"></i>Save Changes</button>
                    <button class="btn btn-light">Cancel</button>
                    <button class="btn btn-outline-primary ms-auto"><i class="mdi mdi-eye-outline me-1"></i>Preview</button>
                </div>

            </div>
        </div>
    </div>

    {{-- Form Validation Demo --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Form Validation</h5>
                <p class="card-subtitle">Bootstrap 5 built-in validation states</p>

                <form class="was-validated" novalidate>
                    <div class="form-group">
                        <label class="form-label">Valid Input</label>
                        <input type="text" class="form-control is-valid" value="John Doe" required>
                        <div class="valid-feedback">Looks good!</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Invalid Input</label>
                        <input type="text" class="form-control is-invalid" required>
                        <div class="invalid-feedback">This field is required.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email (with validation)</label>
                        <input type="email" class="form-control" placeholder="you@example.com" required>
                        <div class="invalid-feedback">Please enter a valid email.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Validate Form</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Input Groups --}}
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Input Groups</h5>
                <p class="card-subtitle">Extend form controls with prepend/append</p>

                <div class="form-group">
                    <label class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text">@</span>
                        <input type="text" class="form-control" placeholder="username">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Budget</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" placeholder="0.00">
                        <span class="input-group-text">.00</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Website</label>
                    <div class="input-group">
                        <span class="input-group-text">https://</span>
                        <input type="text" class="form-control" placeholder="yoursite.com">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Search with Button</label>
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search...">
                        <button class="btn btn-primary" type="button"><i class="mdi mdi-magnify"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function togglePwd(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

// HTML5 form validation
document.querySelectorAll('form.needs-validation').forEach(form => {
    form.addEventListener('submit', e => {
        if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
        form.classList.add('was-validated');
    });
});
</script>
@endpush