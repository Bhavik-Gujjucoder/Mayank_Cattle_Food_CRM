@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('content')
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">{{ ucwords($role->name) }}</h5>
                <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-sm btn-warning">
                    <i class="ti ti-edit"></i> Edit
                </a>
            </div>

            <label class="text-muted small">Permissions</label>
            <div class="d-flex flex-wrap gap-2 mb-3">
                @forelse ($role->permissions as $permission)
                    <span class="badge bg-info">{{ ucwords(str_replace('-', ' ', $permission->name)) }}</span>
                @empty
                    <span class="text-muted">No permissions assigned.</span>
                @endforelse
            </div>

            <a href="{{ route('roles.index') }}" class="btn btn-secondary">Back to list</a>
        </div>
    </div>
@endsection
