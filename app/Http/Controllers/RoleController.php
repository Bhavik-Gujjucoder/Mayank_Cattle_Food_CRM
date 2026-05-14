<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data['page_title'] = 'Role & Permissions';
        if ($request->ajax()) {
            $data = Role::where('name', '!=', 'super admin')->with('permissions');
            return DataTables::of($data)
                ->addIndexColumn()
                // Add permission_name column
                ->addColumn('permission_name', function ($row) {
                    return $row->permissions->map(function ($permission) {
                        return '<span class="badge bg-info">' . e(ucwords(str_replace('-', ' ', $permission->name))) . '</span>';
                    })->implode(' ');
                })
                ->addColumn('action', function ($row) {
                    $edit_btn = '<a href="' . route('roles.edit', $row->id) . '" class="dropdown-item"  data-id="' . $row->id . '"
                    class="btn btn-outline-warning btn-sm edit-btn"><i class="ti ti-edit text-warning"></i> Edit</a>';

                    // $delete_btn = '<a href="javascript:void(0)" class="dropdown-item deleteRole"  data-id="' . $row->id . '"
                    // class="btn btn-outline-warning btn-sm edit-btn"> <i class="ti ti-trash text-danger"></i> ' . __('Delete') . '</a><form action="' . route('roles.destroy', $row->id) . '" method="post" class="delete-form" id="delete-form-' . $row->id . '" >'
                    //     . csrf_field() . method_field('DELETE') . '</form>';

                    $action_btn = '<div class="dropdown table-action">
                                    <a href="#" class="action-icon " data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                                    <div class="dropdown-menu dropdown-menu-right">';

                    // Auth::user()->can('Manage Users') ? $action_btn .= $edit_btn : '';
                    // Auth::user()->can('Manage Users') ? $action_btn .= $delete_btn : '';
                    $action_btn .= $edit_btn ?? '';
                    // $action_btn .= $delete_btn ?? '';

                    return $action_btn . ' </div></div>';
                })
                // Search logic for permissions
                ->filterColumn('permission_name', function ($query, $keyword) {
                    $query->whereHas('permissions', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })

                ->rawColumns(['action', 'permission_name'])
                ->make(true);
        }

        return view('roles.index', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  CREATE                                                            */
    /* ------------------------------------------------------------------ */
    public function create()
    {
        $data['page_title']  = 'Add Role & Permission';
        $data['permissions'] = Permission::whereNull('deleted_at')->get()->groupBy('type');
        $data['dashboard_permissions']  = Permission::where('deleted_at', null)->where('is_dashboard', 1)->get()->all();

        return view('roles.create', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  STORE                                                             */
    /* ------------------------------------------------------------------ */
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|unique:roles,name,NULL,id'], [
            'name.required' => 'The role name field is required.',
            'name.unique'   => 'The role name has already been taken.',
        ]);
        $role = Role::create(['name' => $request->name]);

        if ($request->permissions) {
            $role->givePermissionTo($request->permissions);
        }

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT                                                              */
    /* ------------------------------------------------------------------ */
    public function edit(Role $role)
    {
        // auth()->user()->assignRole('super admin');
        $data['page_title']   = 'Edit Role & Permission';
        $data['permissions'] = Permission::whereNull('deleted_at')->get()->groupBy('type');
        $data['dashboard_permissions']  = Permission::where('deleted_at', null)->where('is_dashboard', 1)->get()->all();

        $data['role']  = $role;
        return view('roles.edit', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                            */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, Role $role)
    {
        $rules = [
            'name' => 'nullable|unique:roles,name,' . ($role->id ?? 'NULL'),
        ];
        if (
            $role !== null &&
            !in_array($role->name, ['admin', 'staff', 'broker', 'transporter', 'dealer'])
        ) {
            $rules['name'] = 'required|' . $rules['name'];
        }

        $request->validate($rules, [
            'name.required' => 'The role name field is required.',
            'name.unique'   => 'The role name has already been taken.',
        ]);

        if (!in_array($role->name, ['admin', 'staff', 'broker', 'transporter', 'dealer'])) {
            $role->update(['name' => $request->name]);
        }
        if ($request->permissions) {
            $permissions = Permission::whereIn('id', $request->permissions)->get();

            $role->syncPermissions($permissions);
        }

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  DESTROY                                                           */
    /* ------------------------------------------------------------------ */
    public function destroy(Role $role)
    {
        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }
}
