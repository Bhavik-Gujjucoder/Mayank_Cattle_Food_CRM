@extends('layouts.main')

@section('content')
@section('title')
    {{ $page_title }}
@endsection
<div class="">
    <div class="">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <form action="{{ route('roles.store') }}" method="POST" id="roleForm">
                        @csrf
                        <div class="mb-3 col-md-6">
                            <label>Role Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control">
                            {{-- @error('name')
                                <span class="invalid-feedback d-block">{{ $message }}</span>
                            @enderror --}}
                        </div>
                        <div class="mb-3 ">
                            <label>All Permissions</label><br>
                            {{-- @foreach ($permissions as $permission)
                                <div class="col-lg-2 col-md-4">
                                    <div class="form-check form-check-md d-flex align-items-center">
                                        <input class="form-check-input" id="{{ $permission->id }}" type="checkbox"
                                            name="permissions[]" style="border : 1px solid #0303038a"
                                            value="{{ $permission->name }}">

                                        <label class="form-check-label"
                                            for="{{ $permission->id }}">{{ $permission->name }} </label>
                                    </div>
                                </div>
                            @endforeach --}}
                            @foreach ($permissions as $type => $items)
                                <div class="mb-3 mt-3">
                                    <label class="fw-bold">{{ ucwords(str_replace('-', ' ', $type)) }}</label>

                                    <div class="row mt-1">
                                        @foreach ($items as $permission)
                                            <div class="col-md-3">
                                                <label>
                                                    <input type="checkbox" name="permissions[]" class="form-check-input"
                                                        value="{{ $permission->id }}">

                                                    {{ ucwords(str_replace('-', ' ', $permission->name)) }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach






                            {{-- <div class="mb-2 mt-5">
                                <h4> Dashboard Permissions</h4>
                            </div> --}}
                            {{-- @foreach ($dashboard_permissions as $dpermission)
                                <div class="col-lg-4 col-md-4">
                                    <div class="form-check form-check-md d-flex align-items-center">
                                        <input class="form-check-input" id="{{ $dpermission->id }}" type="checkbox"
                                            name="permissions[]" style="border : 1px solid #0303038a"
                                            value="{{ $dpermission->name }}">

                                        <label class="form-check-label"
                                            for="{{ $dpermission->id }}">{{ $dpermission->name }} </label>
                                    </div>
                                </div>
                            @endforeach --}}

                        </div>
                        <div class="mt-5">
                            <button type="submit" class="btn btn-success">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function() {
            $('#roleForm').validate({
                rules: {
                    name: {
                        required: true
                    },
                    'permissions[]': {
                        required: true
                    }
                },
                messages: {
                    name: {
                        required: "Role name is required"
                    },
                    'permissions[]': {
                        required: "Please select at least one permission"
                    }
                },
                errorElement: 'span',
                errorClass: 'text-danger', // Add Bootstrap class
                errorPlacement: function(error, element) {
                    if (element.attr("name") == "permissions[]") {
                        error.insertAfter(element.closest('.mb-3')); // Group error after checkbox area
                    } else {
                        error.insertAfter(element);
                    }
                }
            });
        });
    </script>
@endsection
