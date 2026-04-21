<?php

namespace App\Http\Controllers;

use App\Models\CityManagement;
use App\Models\DealerManagement;
use App\Models\StateManagement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;

class DealerManagementController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  Shared validation rules                                           */
    /* ------------------------------------------------------------------ */
    private function rules(int $ignoreId = 0): array
    {
        return [
            'profile_picture'   => 'nullable|mimes:jpg,jpeg,png,gif|max:2048',
            'broker_id'         => 'required|exists:users,id',
            'code_no'           => 'required|string|max:20|unique:dealer_management,code_no,' . $ignoreId,
            'applicant_name'    => 'required|string|max:255',
            'firm_shop_name'    => 'required|string|max:255',
            'firm_shop_address' => 'required|string|max:500',
            'mobile_no'         => 'required|digits:10',
            'pancard'           => 'required|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/i',
            'gstin'             => 'nullable|string|size:15',
            'aadhar_card'       => 'nullable|digits:12',

            'email'             => 'required|email|unique:users,email,' . $ignoreId . ',id,deleted_at,NULL',
            'password'          => 'required|min:6|confirmed',
            'state_id'          => 'required',
            'city_id'           => 'required',
            'postal_code'       => 'nullable|string|max:6',
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE RULES                                                      */
    /* ------------------------------------------------------------------ */
    private function updateRules(int $dealerId, int $userId): array
    {
        return [
            'profile_picture'   => 'nullable|mimes:jpg,jpeg,png,gif|max:2048',
            'broker_id'         => 'required|exists:users,id',
            'code_no'           => 'required|string|max:20|unique:dealer_management,code_no,' . $dealerId,
            'applicant_name'    => 'required|string|max:255',
            'firm_shop_name'    => 'required|string|max:255',
            'firm_shop_address' => 'required|string|max:500',
            'mobile_no'         => 'required|digits:10',
            'pancard'           => 'required|string|size:10|regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/i',
            'gstin'             => 'nullable|string|size:15',
            'aadhar_card'       => 'nullable|digits:12',

            'email'             => 'required|email|unique:users,email,' . $userId . ',id,deleted_at,NULL',
            'password'          => 'nullable|min:6|confirmed',
            'state_id'          => 'required',
            'city_id'           => 'required',
            'postal_code'       => 'nullable|string|max:6',
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  VALIDATION MESSAGES                                               */
    /* ------------------------------------------------------------------ */
    private function messages(): array
    {
        return [
            'broker_id.required'        => 'Please select a broker.',
            'broker_id.exists'          => 'Selected broker is invalid.',
            'code_no.required'          => 'Code no is required.',
            'applicant_name.required'   => 'Dealer name is required.',
            'firm_shop_name.required'   => 'Firm / shop name is required.',
            'firm_shop_address.required' => 'Firm / shop address is required.',
            'mobile_no.required'        => 'Mobile no is required.',
            'mobile_no.digits'          => 'Mobile no must be exactly 10 digits.',
            'pancard.required'          => 'PAN card no is required.',
            'pancard.size'              => 'PAN card must be exactly 10 characters.',
            'pancard.regex'             => 'Invalid PAN format. Expected: AAAAA9999A',
            'gstin.size'                => 'GSTIN must be exactly 15 characters.',
            'aadhar_card.digits'        => 'Aadhar card must be exactly 12 digits.',
            'profile_picture.image'     => 'Profile image must be an image file.',
            'profile_picture.mimes'     => 'Allowed formats: JPG, JPEG, PNG, GIF.',
            'profile_picture.max'       => 'Profile image must not exceed 2MB.',

            'email.required'            => 'Email is required.',
            'email.email'               => 'Please enter a valid email address.',
            'email.unique'              => 'This email is already registered.',
            'password.confirmed'        => 'Password and Confirm Password must match.',

            'state_id.required'         => 'State is required.',
            'state_id.exists'           => 'Selected state is invalid.',
            'city_id.required'          => 'City is required.',
            'city_id.exists'            => 'Selected city is invalid.',
            'postal_code.max'           => 'Postal code cannot exceed 6 characters.',
        ];
    }

    /* ------------------------------------------------------------------ */
    /*  INDEX                                                               */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $data['page_title'] = 'Dealers';
        $data['brokers']    = User::whereHas('roles', fn($q) => $q->where('name', 'broker'))->get();

        if ($request->ajax()) {
            $query = DealerManagement::with('user', 'broker', 'city')
                ->when($request->broker_id && $request->broker_id != 'all',  fn($q) => $q->where('broker_id', $request->broker_id))
                ->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', Carbon::parse($request->start_date)->format('Y-m-d')))
                ->when($request->end_date,   fn($q) => $q->whereDate('created_at', '<=', Carbon::parse($request->end_date)->format('Y-m-d')))
                ;
            return DataTables::of($query)
                 ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    return '
                        <div class="dropdown table-action">
                            <a href="#" class="action-icon" data-bs-toggle="dropdown">
                                <i class="fa fa-ellipsis-v"></i>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a href="' . route('dealer.edit', $row->id) . '" class="dropdown-item">
                                    <i class="ti ti-edit text-warning"></i> Edit
                                </a>
                                <a href="javascript:void(0)" class="dropdown-item delete-btn delete_d_d" data-id="' . $row->id . '">
                                    <i class="ti ti-trash text-danger"></i> Delete
                                </a>
                                <form id="delete-form-' . $row->id . '" action="' . route('dealer.destroy', $row->id) . '" method="POST">
                                    ' . csrf_field() . method_field('DELETE') . '
                                </form>
                            </div>
                        </div>';
                })
                ->addColumn('applicant_name', function ($row) {
                    $user = $row->user;
                    $profilePic = (!empty($user) && !empty($user->profile_picture))
                        ? asset('storage/profile_pictures/' . $user->profile_picture)
                        : asset('images/default-user.png');
                    $name = $user->name ?? '-';
                   $role = $user?->roles?->first()?->name ?? '';
                    return '
                        <div class="d-flex align-items-center">
                            <a href="' . $profilePic . '" target="_blank" class="avatar avatar-sm border rounded p-1 me-2">
                                <img src="' . $profilePic . '" alt="User Image">
                            </a>
                            <div>
                                <div>' . $name . '</div>
                                <small class="text-muted">' . $role . '</small>
                            </div>
                        </div>
                    ';
                })

                ->addColumn('mobile_no', function ($row) {
                    return $row->user?->phone_no ?? '-';
                })

             
                ->editColumn('firm_shop_name', function ($row) {
                    return '<a href="' . route('dealer.show', $row->id) . '" class="open-popup-model" data-id="' . $row->id . '">
                                <i class="ti ti-eye"></i> ' . e($row->firm_shop_name) . '
                            </a>';
                })
                ->addColumn('firm_shop_name_export', function ($row) {
                    return $row->firm_shop_name;
                })

                ->addColumn('applicant_name_export', function ($row) {
                    return $row->user?->name ?? '-';
                })
                ->editColumn('broker_id', fn($row) => $row->broker?->name ?? '-')
                ->editColumn('city_id', fn($row) => $row->city?->city_name ?? '-')
                ->editColumn('created_at', fn($row) => $row->created_at?->format('d M Y') ?? '-')
                ->filterColumn('applicant_name', function ($query, $keyword) {
                    $query->whereHas('user', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('city_id', function ($query, $keyword) {
                    $query->whereHas('city', function ($q) use ($keyword) {
                        $q->where('city_name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('mobile_no', function ($query, $keyword) {
                    $query->whereHas('user', function ($q) use ($keyword) {
                        $q->where('phone_no', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['applicant_name', 'firm_shop_name', 'action'])
                ->make(true);
        }
        return view('dealer.index', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  CREATE                                                              */
    /* ------------------------------------------------------------------ */
    public function create()
    {
        $data['page_title'] = 'Add Dealer';
        $data['brokers']    = User::whereHas('roles', fn($q) => $q->where('name', 'broker'))->get();
        $data['states']    = StateManagement::where('status', 1)->get()->all();
        $nextId             = (DealerManagement::max('id') ?? 0) + 1;
        $data['code_no']    = 'MCF' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

        return view('dealer.create', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  STORE                                                               */
    /* ------------------------------------------------------------------ */
    public function store(Request $request)
    {
        $request->validate($this->rules(), $this->messages());

        $profileImage = null;
        if ($request->hasFile('profile_picture')) {
            $profileImage = basename(
                $request->file('profile_picture')->store('profile_pictures', 'public')
            );
        }

        $user = User::create([
            'profile_picture' => $profileImage,
            'name'     => $request->applicant_name,
            'email'    => $request->email ?? null,
            'phone_no' => $request->mobile_no,
            'password' => Hash::make($request->password),
            'status'   => 1,
        ]);
        $user->assignRole('dealer');

        DealerManagement::create([
            'user_id'           => $user->id,
            'broker_id'         => $request->broker_id,
            'code_no'           => $request->code_no,
            'firm_shop_name'    => $request->firm_shop_name,
            'firm_shop_address' => $request->firm_shop_address,
            'pancard'           => strtoupper($request->pancard),
            'gstin'             => $request->gstin ? strtoupper($request->gstin) : null,
            'aadhar_card'       => $request->aadhar_card ?: null,
            'state_id'          => $request->state_id ?: null,
            'city_id'           => $request->city_id ?: null,
            'postal_code'       => $request->postal_code ?: null,
        ]);

        return redirect()->route('dealer.index')->with('success', 'Dealer created successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  SHOW                                                              */
    /* ------------------------------------------------------------------ */
    public function show(DealerManagement $dealer)
    {
        $data['page_title'] = 'Dealer Details';
        $data['dealer']     = $dealer;
        $html = view('dealer.show', $data)->render();

        return response()->json([
            'html' => $html,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT                                                                */
    /* ------------------------------------------------------------------ */
    public function edit(DealerManagement $dealer)
    {
        $data['page_title'] = 'Edit Dealer';
        $data['dealer']     = $dealer->load('user');
        $data['brokers']    = User::whereHas('roles', fn($q) => $q->where('name', 'broker'))->get();
        $data['states']     = StateManagement::where('status', 1)->get()->all();
        $data['cities']     = CityManagement::where('state_id', $dealer->state_id)->where('status', 1)->get();

        return view('dealer.edit', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                              */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, DealerManagement $dealer)
    {
        $dealer->load('user');
        $request->validate($this->updateRules($dealer->id, $dealer->user_id), $this->messages());

        if ($request->hasFile('profile_picture')) {
            if ($dealer->user->profile_picture) {
                Storage::disk('public')->delete('profile_pictures/' . $dealer->user->profile_picture);
            }
            $profileImage = basename(
                $request->file('profile_picture')->store('profile_pictures', 'public')
            );
        } else {
            $profileImage = $dealer->user->profile_picture;
        }

        $user = [
            'profile_picture' => $profileImage,
            'name'            => $request->applicant_name,
            'email'           => $request->email,
            'phone_no'        => $request->mobile_no,
        ];
        if ($request->filled('password')) {
            $user['password'] = Hash::make($request->password);
        }
        $dealer->user->update($user);

        $dealer->update([
            'broker_id'         => $request->broker_id,
            'code_no'           => $request->code_no,
            'firm_shop_name'    => $request->firm_shop_name,
            'firm_shop_address' => $request->firm_shop_address,
            'pancard'           => strtoupper($request->pancard),
            'gstin'             => $request->gstin ? strtoupper($request->gstin) : null,
            'aadhar_card'       => $request->aadhar_card ?: null,
            'state_id'          => $request->state_id ?: null,
            'city_id'           => $request->city_id ?: null,
            'postal_code'       => $request->postal_code ?: null,
        ]);

        return redirect()->route('dealer.index')->with('success', 'Dealer updated successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  DESTROY                                                             */
    /* ------------------------------------------------------------------ */
    public function destroy(DealerManagement $dealer)
    {
        if ($dealer->user->profile_picture) {
            Storage::disk('public')->delete('profile_pictures/' . $dealer->user->profile_picture);
        }
        $dealer->user->delete();
        $dealer->delete();
        return redirect()->route('dealer.index')->with('success', 'Dealer deleted successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  GET CITIES BY STATE                                                 */
    /* ------------------------------------------------------------------ */
    public function getCitiesByState(Request $request)
    {
        $cities = CityManagement::where('state_id', $request->state_id)->where('status', 1)->get();
        return response()->json($cities);
    }
}
