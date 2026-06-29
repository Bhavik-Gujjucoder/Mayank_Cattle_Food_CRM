@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">{{ $user->name }}</h5>
                <a href="{{ route('users.edit', ['type' => $type, 'id' => $user->id]) }}" class="btn btn-sm btn-warning">
                    <i class="ti ti-edit"></i> Edit
                </a>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="text-muted small">Email</label>
                    <div>{{ $user->email ?: '—' }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="text-muted small">Mobile</label>
                    <div>{{ $user->phone_no ?: '—' }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="text-muted small">Role</label>
                    <div>{{ $user->roles->pluck('name')->implode(', ') ?: '—' }}</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="text-muted small">Status</label>
                    <div>{!! $user->statusBadge() !!}</div>
                </div>
            </div>

            <a href="{{ route('users.index', $type) }}" class="btn btn-secondary">Back to list</a>
        </div>
    </div>
@endsection
