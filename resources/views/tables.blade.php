@extends('layouts.app')

@section('title', 'Basic Table')
@section('page_header', 'Basic Table')
@section('page_icon', 'mdi mdi-table-large')

@section('breadcrumb')
    <li class="breadcrumb-item active">Tables</li>
    <li class="breadcrumb-item active" aria-current="page">Basic Table</li>
@endsection

@section('content')

{{-- Simple Table --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="card-title mb-0">Users Table</h5>
                        <p class="card-subtitle">A simple example with hover state</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary"><i class="mdi mdi-plus me-1"></i>Add New</button>
                        <button class="btn btn-sm btn-light"><i class="mdi mdi-download-outline me-1"></i>Export</button>
                    </div>
                </div>

                {{-- Search & Filter Bar --}}
                <div class="row mb-3 g-2">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="mdi mdi-magnify"></i></span>
                            <input type="text" class="form-control" placeholder="Search records...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select">
                            <option>All Status</option>
                            <option>Active</option>
                            <option>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select">
                            <option>All Roles</option>
                            <option>Admin</option>
                            <option>User</option>
                            <option>Manager</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                    </div>
                                </th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $users = [
                                    ['name'=>'Alice Johnson',   'email'=>'alice@example.com',   'role'=>'Admin',   'status'=>'active',   'joined'=>'Jan 10, 2024'],
                                    ['name'=>'Bob Smith',       'email'=>'bob@example.com',     'role'=>'Manager', 'status'=>'active',   'joined'=>'Feb 5, 2024'],
                                    ['name'=>'Carol White',     'email'=>'carol@example.com',   'role'=>'User',    'status'=>'inactive', 'joined'=>'Mar 22, 2024'],
                                    ['name'=>'David Brown',     'email'=>'david@example.com',   'role'=>'User',    'status'=>'active',   'joined'=>'Apr 1, 2024'],
                                    ['name'=>'Eva Green',       'email'=>'eva@example.com',     'role'=>'Manager', 'status'=>'inactive', 'joined'=>'May 14, 2024'],
                                    ['name'=>'Frank Miller',    'email'=>'frank@example.com',   'role'=>'User',    'status'=>'active',   'joined'=>'Jun 8, 2024'],
                                ];
                                $bgColors = ['1A4A7A','2B6CB0','319795','C05621','276749','9B2C2C'];
                            @endphp

                            @foreach($users as $i => $user)
                            <tr>
                                <td>
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox">
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($user['name']) }}&background={{ $bgColors[$i] }}&color=fff&size=34" alt="" class="rounded-circle">
                                        <span class="fw-medium">{{ $user['name'] }}</span>
                                    </div>
                                </td>
                                <td class="text-muted">{{ $user['email'] }}</td>
                                <td>
                                    @if($user['role'] === 'Admin')
                                        <span class="badge badge-danger">{{ $user['role'] }}</span>
                                    @elseif($user['role'] === 'Manager')
                                        <span class="badge badge-primary">{{ $user['role'] }}</span>
                                    @else
                                        <span class="badge badge-info">{{ $user['role'] }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user['status'] === 'active')
                                        <span class="d-flex align-items-center gap-1 text-success small fw-semibold">
                                            <i class="mdi mdi-circle fs-6" style="font-size:0.6rem!important"></i> Active
                                        </span>
                                    @else
                                        <span class="d-flex align-items-center gap-1 text-muted small fw-semibold">
                                            <i class="mdi mdi-circle fs-6" style="font-size:0.6rem!important"></i> Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $user['joined'] }}</td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-icon btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="View">
                                            <i class="mdi mdi-eye-outline"></i>
                                        </button>
                                        <button class="btn btn-icon btn-sm btn-light" data-bs-toggle="tooltip" title="Edit">
                                            <i class="mdi mdi-pencil-outline"></i>
                                        </button>
                                        <button class="btn btn-icon btn-sm btn-light text-danger" data-bs-toggle="tooltip" title="Delete" data-confirm="Are you sure you want to delete this user?">
                                            <i class="mdi mdi-trash-can-outline"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <p class="text-muted small mb-0">Showing <strong>1–6</strong> of <strong>48</strong> entries</p>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled"><a class="page-link" href="#">&laquo;</a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item"><a class="page-link" href="#">&raquo;</a></li>
                        </ul>
                    </nav>
                </div>

            </div>
        </div>
    </div>
</div>


@endsection

@push('styles')
<style>
.pagination .page-link {
    border-radius: 8px !important;
    margin: 0 2px;
    border-color: var(--border-color);
    color: var(--text-secondary);
    font-size: 0.82rem;
    padding: 0.35rem 0.65rem;
}
.pagination .page-item.active .page-link {
    background: linear-gradient(135deg, var(--primary), var(--primary-light));
    border-color: transparent;
    color: #fff;
}
.pagination .page-link:hover { background: var(--primary-soft); color: var(--primary); }
.table-striped > tbody > tr:nth-of-type(odd) > td { background: var(--primary-soft); }
.table-bordered td, .table-bordered th { border-color: var(--border-color); }
</style>
@endpush