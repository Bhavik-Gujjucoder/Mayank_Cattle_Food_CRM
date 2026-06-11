<?php

namespace App\Http\Controllers;

use App\Models\CityManagement;
use App\Models\StateManagement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class SupplierController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  INDEX                                                               */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $data['page_title'] = 'Supplier Management';
        $data['states']     = StateManagement::where('status', 1)->orderBy('state_name')->get();

        if ($request->ajax()) {
            $query = Supplier::with(['city', 'state']);

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->filled('state_id') && $request->state_id !== 'all') {
                $query->where('state_id', $request->state_id);
            }

            if ($request->filled('city_id') && $request->city_id !== 'all') {
                $query->where('city_id', $request->city_id);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<label class="checkboxs">
                                <input type="checkbox" class="checkbox-item supplier_checkbox" data-id="' . $row->id . '">
                                <span class="checkmarks"></span>
                            </label>';
                })
                ->addColumn('action', function ($row) {
                    $edit_btn = '<a href="javascript:void(0)" class="dropdown-item edit-supplier-btn" data-id="' . $row->id . '">
                                    <i class="ti ti-edit text-warning"></i> Edit
                                </a>';

                    $delete_btn = '<a href="javascript:void(0)" class="dropdown-item delete-supplier-btn" data-id="' . $row->id . '">
                                    <i class="ti ti-trash text-danger"></i> Delete
                                </a>
                                <form action="' . route('supplier.destroy', $row->id) . '" method="POST"
                                    class="delete-form" id="delete-supplier-form-' . $row->id . '">
                                    ' . csrf_field() . method_field('DELETE') . '
                                </form>';

                    $action_btn = auth()->user()->can('edit-supplier') ? $edit_btn : '';
                    $action_btn .= auth()->user()->can('delete-supplier') ? $delete_btn : '';

                    return '<div class="dropdown table-action">
                                <a href="#" class="action-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa fa-ellipsis-v"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">'
                                    . $action_btn .
                                '</div>
                            </div>';
                })
                ->addColumn('city_name', fn ($row) => e($row->city?->city_name ?? '—'))
                ->editColumn('mobile', fn ($row) => $row->mobile ?? '-')
                ->editColumn('email', fn ($row) => $row->email ?? '-')
                ->editColumn('address', function ($row) {
                    return $row->address
                        ? '<span title="' . e($row->address) . '">' . e(\Str::limit($row->address, 40)) . '</span>'
                        : '-';
                })
                ->editColumn('status', fn ($row) => $row->statusBadge())
                ->filterColumn('city_name', function ($query, $keyword) {
                    $query->whereHas('city', function ($q) use ($keyword) {
                        $q->where('city_name', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['checkbox', 'address', 'status', 'action'])
                ->make(true);
        }

        return view('supplier.index', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  STORE                                                               */
    /* ------------------------------------------------------------------ */
    public function store(Request $request)
    {
        $validated = $request->validate(
            $this->validationRules(),
            $this->validationMessages()
        );

        $this->validateCityBelongsToState($request);

        Supplier::create($this->prepareSupplierData($validated));

        return response()->json(['success' => true, 'message' => 'Supplier created successfully.']);
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT                                                                */
    /* ------------------------------------------------------------------ */
    public function edit(Supplier $supplier)
    {
        return response()->json($supplier->load(['state', 'city']));
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                              */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate(
            $this->validationRules($supplier->id),
            $this->validationMessages()
        );

        $this->validateCityBelongsToState($request);

        $supplier->update($this->prepareSupplierData($validated));

        return response()->json(['success' => true, 'message' => 'Supplier updated successfully.']);
    }

    /* ------------------------------------------------------------------ */
    /*  DESTROY                                                             */
    /* ------------------------------------------------------------------ */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()->route('supplier.index')->with('success', 'Supplier deleted successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  BULK DELETE                                                         */
    /* ------------------------------------------------------------------ */
    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;

        if (! empty($ids)) {
            Supplier::whereIn('id', $ids)->delete();

            return response()->json(['message' => 'Selected suppliers deleted successfully.']);
        }

        return response()->json(['message' => 'No records selected.'], 400);
    }

    private function validationRules(?int $supplierId = null): array
    {
        return [
            'name'            => 'required|string|max:255',
            'mobile'          => 'nullable|string|max:20',
            'email'           => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('suppliers', 'email')->ignore($supplierId)->whereNull('deleted_at'),
            ],
            'address'         => 'nullable|string',
            'opening_balance' => 'nullable|numeric|min:0',
            'state_id'        => 'required|exists:state_management,id',
            'city_id'         => 'required|exists:city_management,id',
            'status'          => 'nullable|in:0,1',
        ];
    }

    private function prepareSupplierData(array $validated): array
    {
        return [
            'name'            => $validated['name'],
            'mobile'          => filled($validated['mobile'] ?? null) ? $validated['mobile'] : null,
            'email'           => filled($validated['email'] ?? null) ? $validated['email'] : null,
            'address'         => filled($validated['address'] ?? null) ? $validated['address'] : null,
            'opening_balance' => $validated['opening_balance'] ?? 0,
            'state_id'        => $validated['state_id'],
            'city_id'         => $validated['city_id'],
            'status'          => $validated['status'] ?? 1,
        ];
    }

    private function validateCityBelongsToState(Request $request): void
    {
        $valid = CityManagement::where('id', $request->city_id)
            ->where('state_id', $request->state_id)
            ->where('status', 1)
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'city_id' => 'Selected city does not belong to the selected state.',
            ]);
        }
    }

    private function validationMessages(): array
    {
        return [
            'name.required'     => 'Supplier name is required.',
            'email.email'       => 'Please enter a valid email address.',
            'email.unique'      => 'Email address already exists.',
            'state_id.required' => 'State is required.',
            'state_id.exists'   => 'Selected state is invalid.',
            'city_id.required'  => 'City is required.',
            'city_id.exists'    => 'Selected city is invalid.',
        ];
    }
}
