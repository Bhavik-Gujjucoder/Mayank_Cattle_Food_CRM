@extends('layouts.main')
@section('title')
    {{ $page_title }}
@endsection
@section('styles')
    @include('raw_material.partials.module-responsive')
@endsection
@section('content')

<div class="raw-material-module">
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <p class="form-section-title mb-0"><i class="ti ti-category me-1"></i>Category</p>
            <div class="d-flex gap-2">
                @can('edit-raw-material-category')
                    <a href="{{ route('raw-material.category.edit', $category->id) }}" class="btn btn-warning btn-sm">
                        <i class="ti ti-edit me-1"></i>Edit
                    </a>
                @endcan
                <a href="{{ route('raw-material.category.index') }}" class="btn btn-light btn-sm">
                    <i class="ti ti-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>
        <div class="row">
            <div class="col-12 col-sm-6 col-md-3 mb-3">
                <label class="col-form-label text-muted">Category ID</label>
                <div class="fw-semibold">{{ $category->category_unique_id }}</div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 mb-3">
                <label class="col-form-label text-muted">Name</label>
                <div class="fw-semibold">{{ $category->name }}</div>
            </div>
            <div class="col-12 col-sm-6 col-md-3 mb-3">
                <label class="col-form-label text-muted">Status</label>
                <div>{!! $category->statusBadge() !!}</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <p class="form-section-title"><i class="ti ti-packages me-1"></i>Materials in this Category ({{ $materials->count() }})</p>
        <div class="table-responsive custom-table">
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Material ID</th>
                        <th>Name</th>
                        <th>Unit</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($materials as $material)
                        <tr>
                            <td>
                                <a href="{{ route('raw-material.show', $material->id) }}">{{ $material->raw_material_unique_id }}</a>
                            </td>
                            <td>{{ $material->name }}</td>
                            <td>{{ $material->unit }}</td>
                            <td>{!! $material->statusBadge() !!}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted text-center">No materials linked yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

@endsection
