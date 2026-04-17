<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{
    public function index(Request $request, $type)
    {
        $data['page_title'] = match ($type) {
            'broker' => 'Broker Management',
            'transporter' => 'Transporter Management',
            default => 'Users Management',
        };
        $data['type'] = $type;
        $data['users'] = User::when($type, function ($query) use ($type) {
            if ($type == 'broker') {
                $query->whereHas('roles', function ($q) use ($type) {
                    $q->where('name', 'broker');
                });
            } else if ($type == 'transporter') {
                $query->whereHas('roles', function ($q) use ($type) {
                    $q->where('name', 'transporter');
                });
            } else {
                $query->whereHas('roles', function ($q) use ($type) {
                    $q->whereIn('name', ['admin', 'staff']);
                });
            }
        });
        if ($request->ajax()) {
            /* $data = User::whereHas('roles', function ($q) {
                $q->where('name', '!=', 'sales');
            });*/
            return DataTables::of($data['users'])
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<label class="checkboxs">
                            <input type="checkbox" class="checkbox-item user_checkbox" data-id="' . $row->id . '">
                            <span class="checkmarks"></span>
                        </label>';
                })
                ->addColumn('action', function ($row) use ($type) {
                    $show_btn = '<a href="' . route('users.show', ['type' => $type, 'id' => $row->id]) . '"
                        class="btn btn-outline-info btn-sm">
                        <i class="bi bi-eye-fill"></i> ' . __('Show') . '
                    </a>';
                    $edit_btn = '<a href="' . route('users.edit', ['type' => $type, 'id' => $row->id]) . '"
                        class="dropdown-item edit-btn" data-id="' . $row->id . '">
                        <i class="ti ti-edit text-warning"></i> Edit
                    </a>';
                    $delete_btn = '
                        <a href="javascript:void(0)"
                            class="dropdown-item deleteUser"
                            data-id="' . $row->id . '">
                            <i class="ti ti-trash text-danger"></i> ' . __('Delete') . '
                        </a>
                        <form action="' . route('users.destroy', ['type' => $type, 'id' => $row->id]) . '"
                            method="POST"
                            class="delete-form"
                            id="delete-form-' . $row->id . '"
                            style="display:none;">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                        </form>';
                    $action_btn = '<div class="dropdown table-action">
                                             <a href="#" class="action-icon " data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                                             <div class="dropdown-menu dropdown-menu-right">';
                    $action_btn .= $edit_btn;
                    $action_btn .= $delete_btn;
                    return $action_btn . ' </div></div>';
                })
                // ->editColumn('name', function ($row) {
                //     $profilePic = isset($row->profile_picture)
                //         ? asset('storage/profile_pictures/' . $row->profile_picture)
                //         : asset('images/default-user.png');
                //     return '
                //         <a href="' . $profilePic . '" target="_blank" class="avatar avatar-sm border rounded p-1 me-2">
                //             <img src="' . $profilePic . '" alt="User Image">
                //         </a>' . $row->name;
                // })

                ->editColumn('name', function ($row) {
                    $profilePic = $row->profile_picture
                        ? asset('storage/profile_pictures/' . $row->profile_picture)
                        : asset('images/default-user.png');
                   $role = $row->roles->pluck('name')->implode(', ');
                    return '
                        <div class="d-flex align-items-center">
                            <a href="' . $profilePic . '" target="_blank" class="avatar avatar-sm border rounded p-1 me-2">
                                <img src="' . $profilePic . '" alt="User Image">
                            </a>
                            <div>
                                <div>' . $row->name . '</div>
                                <small class="text-muted">' . $role . '</small>
                            </div>
                        </div>
                    ';
                })
                ->addColumn('role', function ($user) {
                    return $user->roles->pluck('name')->implode(', ');
                })
                ->addColumn('role', function ($user) {
                    $roles = $user->roles
                        ->where('name', '!=', 'sales')
                        ->pluck('name')
                        ->implode(', ');
                    return $roles ?: '-';
                })
                ->editColumn('status', function ($user) {
                    return $user->statusBadge();
                })
                ->editColumn('email', function ($user) {
                    return $user->email ? $user->email : '-';
                })
                ->editColumn('created_at', function ($user) {
                    return  $user->created_at->format('d M Y, h:i A');
                })
                ->filterColumn('created_at', function ($query, $keyword) {
                    $query->whereRaw("DATE_FORMAT(created_at, '%d %b %Y, %h:i %p') like ?", ["%{$keyword}%"]);
                })
                ->filterColumn('role', function ($query, $keyword) {
                    $query->whereHas('roles', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['action', 'status', 'checkbox', 'name'])
                ->make(true);
        }
        return view('users.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($type = 'user')
    {
        $data['page_title'] = match ($type) {
            'broker' => 'Add Broker',
            'transporter' => 'Add Transporter',
            default => 'Add User',
        };
        $data['type']  = $type;
        $data['roles'] = Role::whereIn('name', ['admin', 'staff'])->pluck('name', 'id');
        return view('users.create', $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $type)
    {
        $request->validate([
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Image validation
            'name'     => 'required|string|max:255|unique:users,name,NULL,id,deleted_at,NULL',
            'email'    => 'required|email|unique:users,email,NULL,id,deleted_at,NULL',
            'role'     => [
                'nullable',
                Rule::exists('roles', 'id')->whereNot('name', 'super admin') // Exclude super admin
            ],
            'phone_no' => 'required|digits_between:10,11|unique:users,phone_no,NULL,id,deleted_at,NULL',
            'password' => 'required|min:6|confirmed',
            'status'   => 'required|in:1,0'
        ], [
            'profile_picture.image' => 'The profile picture must be an image.',
            'profile_picture.mimes' => 'The profile picture must be a file of type: JPG, JPEG, PNG, or GIF.',
            'profile_picture.max'   => 'The profile picture may not be greater than 2MB.',
            'role.exists'           => 'Invalid role selected.',
            'password.confirmed'    => 'Password and Confirm Password must match.'
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email ?? null,
            'phone_no' => $request->phone_no,
            'password' => Hash::make($request->password),
            'status'   => $request->status,
        ]);

        /* Handle profile picture upload */
        if ($request->hasFile('profile_picture')) {
            $file     = $request->file('profile_picture');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('profile_pictures', $filename, 'public'); // Save to storage/app/public/profile_pictures
            $user->profile_picture = $filename;
        }

        $user->save();
        /* Assign role */
        /* Only assign role if normal user */
        if ($type === 'user') {
            $user->assignRole(Role::find($request->role)->name);
        } elseif ($type == 'broker') {
            $user->assignRole('broker');
        } elseif ($type == 'transporter') {
            $user->assignRole('transporter');
        }

        // **** EMAIL ****
        /* $data['name'] = $request->name;
         $data['email'] = $request->email ?? null;
         $data['password'] = $request->password;

         if ($request->email) {
             Mail::send('email.user_email.create', ['data' => $data], fn($message) => $message->to($user->email)->subject('User Account Created'));
         }*/

        return redirect()->route('users.index', $type)->with('success', ucfirst($type) . ' created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($type, $id)
    {
        $user = User::find($id);

        $data['page_title'] = match ($type) {
            'broker' => 'Edit Broker',
            'transporter' => 'Edit Transporter',
            default => 'Edit User',
        };

        $data['user']       = $user;
        $data['type']       = $type;
        $data['roles']      = Role::whereIn('name', ['admin', 'staff'])->pluck('name', 'id');
        return view('users.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $type, $id)
    {
        $user = User::find($id);
        $request->validate([
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Image validation
            'name'            => 'required|string|max:255|unique:users,name,' . $user->id . ',id,deleted_at,NULL',
            'email'           => 'required|email|unique:users,email,' . $user->id . ',id,deleted_at,NULL',
            'phone_no'        => 'required|numeric|digits_between:10,11|unique:users,phone_no,' . $user->id . ',id,deleted_at,NULL',
            'role'            => 'exists:roles,name',
            'password'        => ['nullable', 'string', 'min:6', 'confirmed'],
            'status'          => 'required|in:0,1',
        ], [
            'password.confirmed' => 'Password and Confirm Password must match.',
        ]);

        $user->update([
            'name'     => $request->name,
            'email'    => $request->email ?? null,
            'phone_no' => $request->phone_no,
            'status'   => $request->status,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        if ($request->hasFile('profile_picture')) {
            /* Delete old profile picture if exists */
            if ($user->profile_picture) {
                Storage::disk('public')->delete('profile_pictures/' . $user->profile_picture);
            }
            /* Upload new profile picture */
            $file     = $request->file('profile_picture');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('profile_pictures', $filename, 'public'); // Save in storage/app/public/profile_pictures

            /* Save new filename in database */
            $user->profile_picture = $filename;
        }
        $user->save();
        if (!$user->hasRole('super admin')) {
            $user->syncRoles([$request->role]); /* Update role */
        }
        if ($type === 'user') {
            $user->syncRoles([$request->role]);
        } elseif ($type == 'broker') {
            $user->syncRoles(['broker']);
        } elseif ($type == 'transporter') {
            $user->syncRoles(['transporter']);
        }
        // **** EMAIL ****
        // if ($user->email) {
        //     if ($user->status === "0") {
        //         $data = [];
        //         $data['name'] = $user->name;
        //         Mail::send('email.user_email.deactive_email', ['data' => $data], fn($message) => $message->to($user->email)->subject('Account Deactivated'));
        //     } else {
        //         $data = [];
        //         $data['name'] = $request->name;
        //         Mail::send('email.user_email.active_email', ['data' => $data], fn($message) => $message->to($user->email)->subject('Account Activated'));
        //     }
        // }

        return redirect()->route('users.index', $type)->with('success', ucfirst($type) . ' updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($type, $id)
    {
        $user = User::findOrFail($id);
        if ($user->profile_picture) {
            Storage::disk('public')->delete('profile_pictures/' . $user->profile_picture);
        }
        $user->delete();
        return redirect()->route('users.index', $type)->with('success', ucfirst($type) . ' deleted successfully.');
    }

    public function bulkDelete(Request $request, $type)
    {
        $ids = $request->ids;
        if (!empty($ids) && is_array($ids)) {
            $users = User::whereIn('id', $ids)->get();
            foreach ($users as $u) {
                if ($u->profile_picture) {
                    Storage::disk('public')->delete('profile_pictures/' . $u->profile_picture);
                }
                $u->delete();
            }
            return response()->json(['message' => 'Selected ' . ucfirst($type) . 's deleted successfully!']);
        }
        return response()->json(['message' => 'No records selected!'], 400);
    }
}
