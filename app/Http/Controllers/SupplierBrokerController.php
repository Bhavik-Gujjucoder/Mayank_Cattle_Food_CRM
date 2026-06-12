<?php

namespace App\Http\Controllers;

use App\Models\CityManagement;
use App\Models\StateManagement;
use App\Models\SupplierBroker;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\DataTables;

class SupplierBrokerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view-supplier-broker')->only(['index']);
    }

    public function index(Request $request)
    {
        $data['page_title'] = 'Supplier Broker Management';
        $data['states']     = StateManagement::where('status', 1)->orderBy('state_name')->get();

        if ($request->ajax()) {
            $canEdit   = auth()->user()->can('edit-supplier-broker');
            $canDelete = auth()->user()->can('delete-supplier-broker');

            $query = SupplierBroker::with(['city', 'state']);

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
                                <input type="checkbox" class="checkbox-item supplier_broker_checkbox" data-id="' . $row->id . '">
                                <span class="checkmarks"></span>
                            </label>';
                })
                ->addColumn('action', function ($row) use ($canEdit, $canDelete) {
                    $edit_btn = '<a href="javascript:void(0)" class="dropdown-item edit-supplier-broker-btn" data-id="' . $row->id . '">
                                    <i class="ti ti-edit text-warning"></i> Edit
                                </a>';

                    $delete_btn = '<a href="javascript:void(0)" class="dropdown-item delete-supplier-broker-btn" data-id="' . $row->id . '">
                                    <i class="ti ti-trash text-danger"></i> Delete
                                </a>
                                <form action="' . route('supplier-broker.destroy', $row->id) . '" method="POST"
                                    class="delete-form" id="delete-supplier-broker-form-' . $row->id . '">
                                    ' . csrf_field() . method_field('DELETE') . '
                                </form>';

                    $action_btn = $canEdit ? $edit_btn : '';
                    $action_btn .= $canDelete ? $delete_btn : '';

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

        return view('supplier_broker.index', $data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(
            $this->validationRules(),
            $this->validationMessages()
        );

        $this->validateCityBelongsToState($request);

        SupplierBroker::create($this->prepareData($validated));

        return response()->json(['success' => true, 'message' => 'Supplier broker created successfully.']);
    }

    public function edit(SupplierBroker $supplierBroker)
    {
        return response()->json($supplierBroker->load(['state', 'city']));
    }

    public function update(Request $request, SupplierBroker $supplierBroker)
    {
        $validated = $request->validate(
            $this->validationRules($supplierBroker->id),
            $this->validationMessages()
        );

        $this->validateCityBelongsToState($request);

        $supplierBroker->update($this->prepareData($validated));

        return response()->json(['success' => true, 'message' => 'Supplier broker updated successfully.']);
    }

    public function destroy(SupplierBroker $supplierBroker)
    {
        if ($supplierBroker->suppliers()->exists()) {
            return redirect()->route('supplier-broker.index')
                ->with('error', 'Cannot delete — this supplier broker has linked suppliers.');
        }

        $supplierBroker->delete();

        return redirect()->route('supplier-broker.index')->with('success', 'Supplier broker deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;

        if (empty($ids)) {
            return response()->json(['message' => 'No records selected.'], 400);
        }

        $blocked = SupplierBroker::whereIn('id', $ids)
            ->whereHas('suppliers')
            ->pluck('name')
            ->all();

        if (! empty($blocked)) {
            return response()->json([
                'message' => 'Cannot delete supplier broker(s) with linked suppliers: ' . implode(', ', $blocked) . '.',
            ], 422);
        }

        SupplierBroker::whereIn('id', $ids)->delete();

        return response()->json(['message' => 'Selected supplier brokers deleted successfully.']);
    }

    private function validationRules(?int $supplierBrokerId = null): array
    {
        return [
            'name'            => 'required|string|max:255',
            'mobile'          => 'nullable|string|max:20',
            'email'           => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('supplier_brokers', 'email')->ignore($supplierBrokerId)->whereNull('deleted_at'),
            ],
            'address'         => 'nullable|string',
            'opening_balance' => 'nullable|numeric|min:0',
            'state_id'        => 'required|exists:state_management,id',
            'city_id'         => 'required|exists:city_management,id',
            'status'          => 'nullable|in:0,1',
        ];
    }

    private function prepareData(array $validated): array
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
            'name.required'     => 'Supplier broker name is required.',
            'email.email'       => 'Please enter a valid email address.',
            'email.unique'      => 'Email address already exists.',
            'state_id.required' => 'State is required.',
            'state_id.exists'   => 'Selected state is invalid.',
            'city_id.required'  => 'City is required.',
            'city_id.exists'    => 'Selected city is invalid.',
        ];
    }
}
