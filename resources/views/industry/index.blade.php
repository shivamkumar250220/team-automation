@extends('layouts.app')

@section('title', 'Industry List')
@section('page_header', 'Industry List')
@section('page_icon', 'mdi mdi-account-multiple')

@section('breadcrumb')
    <li class="breadcrumb-item active">Industry List</li>
@endsection

@push('styles')
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
@endpush

@section('content')

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                    <a href="{{ route('industry.create') }}" class="btn btn-primary btn-sm">
                        <i class="mdi mdi-plus me-1"></i> Add Industry
                    </a>
                    <div id="dt-controls" class="d-flex align-items-center gap-2"></div>
                </div>

                <table id="industry-table" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Name</th>
                            <th width="70" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($industry as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $item->name }}</td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                        <i class="mdi mdi-dots-horizontal"></i>
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('industry.edit', $item->id) }}">
                                                Edit
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#"
                                            onclick="deleteIndustry({{ $item->id }}); return false;">
                                                Delete
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

<script>
    function deleteIndustry(id) {
        if (!confirm('Are you sure you want to delete this industry?')) return;

        fetch(`/industry/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message ?? 'Industry deleted successfully.');
                window.location.reload();
            } else {
                alert(data.message ?? 'Failed to delete industry.');
            }
        })
        .catch(() => alert('Something went wrong. Please try again.'));
    }
</script>

@endsection
