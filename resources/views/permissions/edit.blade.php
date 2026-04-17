@extends('layouts.main')

@section('content')
@section('title')
    {{ $page_title }}
@endsection
<div class="container">
    <div class="card">
        <div class="card-body">
            <div class="row">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('permissions.update', $permission->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label>Permission Name</label>
                        <input type="text" name="name" class="form-control" value="{{ $permission->name }}"
                            required>
                    </div>

                    <button type="submit" class="btn btn-success">Update</button>
                    <a href="{{ route('permissions.index') }}" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
