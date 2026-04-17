@extends('layouts.app')

@section('title', 'Dashboard')

@section('page_header', 'Dashboard')
@section('page_icon', 'mdi mdi-view-dashboard-outline')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Overview</li>
@endsection

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

@endsection