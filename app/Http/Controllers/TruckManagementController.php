<?php

namespace App\Http\Controllers;

use App\Models\Truck;
use App\Models\User;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class TruckManagementController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  INDEX                                                               */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $data['page_title']   = 'Truck Management';
        $data['transporters'] = User::role('transporter')
            ->where('status', 1)
            ->orderBy('name')
            ->get();

        if ($request->ajax()) {
            $query = Truck::with('transporter');

            if ($request->filled('transporter_id') && $request->transporter_id !== 'all') {
                $query->where('transporter_id', $request->transporter_id);
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<label class="checkboxs">
                                <input type="checkbox" class="checkbox-item truck_checkbox" data-id="' . $row->id . '">
                                <span class="checkmarks"></span>
                            </label>';
                })
                ->addColumn('transporter_name', fn($row) => $row->transporter?->name ?? '—')
                ->editColumn('status', fn($row) => $row->statusBadge())
                ->addColumn('action', function ($row) {
                    $edit_btn = auth()->user()->can('edit-truck')
                        ? '<a href="javascript:void(0)" class="dropdown-item edit-truck-btn" data-id="' . $row->id . '">
                               <i class="ti ti-edit text-warning"></i> Edit
                           </a>'
                        : '';

                    $delete_btn = auth()->user()->can('delete-truck')
                        ? '<a href="javascript:void(0)" class="dropdown-item deleteTruck" data-id="' . $row->id . '">
                               <i class="ti ti-trash text-danger"></i> Delete
                           </a>
                           <form action="' . route('truck.destroy', $row->id) . '" method="POST"
                                 class="delete-truck-form" id="delete-truck-form-' . $row->id . '">'
                          . csrf_field() . method_field('DELETE') .
                          '</form>'
                        : '';

                    if (! $edit_btn && ! $delete_btn) {
                        return '—';
                    }

                    return '<div class="dropdown table-action">
                                <a href="#" class="action-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa fa-ellipsis-v"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">'
                                    . $edit_btn . $delete_btn .
                               '</div>
                            </div>';
                })
                ->rawColumns(['checkbox', 'status', 'action'])
                ->make(true);
        }

        return view('truck.index', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  STORE                                                               */
    /* ------------------------------------------------------------------ */
    public function store(Request $request)
    {
        $request->validate([
            'transporter_id' => 'required|exists:users,id',
            'truck_number'   => 'required|string|max:50|unique:trucks,truck_number,NULL,id,deleted_at,NULL',
            'status'         => 'required|in:0,1',
        ], [
            'transporter_id.required' => 'Please select a transporter.',
            'transporter_id.exists'   => 'Selected transporter is invalid.',
            'truck_number.required'   => 'Truck number is required.',
            'truck_number.max'        => 'Truck number must not exceed 50 characters.',
            'truck_number.unique'     => 'This truck number already exists.',
            'status.required'         => 'Status is required.',
        ]);

        Truck::create([
            'transporter_id' => $request->transporter_id,
            'truck_number'   => strtoupper(trim($request->truck_number)),
            'status'         => $request->status,
        ]);

        return response()->json(['success' => true, 'message' => 'Truck added successfully.']);
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT                                                                */
    /* ------------------------------------------------------------------ */
    public function edit(Truck $truck)
    {
        return response()->json($truck);
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                              */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, Truck $truck)
    {
        $request->validate([
            'transporter_id' => 'required|exists:users,id',
            'truck_number'   => 'required|string|max:50|unique:trucks,truck_number,' . $truck->id . ',id,deleted_at,NULL',
            'status'         => 'required|in:0,1',
        ], [
            'transporter_id.required' => 'Please select a transporter.',
            'transporter_id.exists'   => 'Selected transporter is invalid.',
            'truck_number.required'   => 'Truck number is required.',
            'truck_number.max'        => 'Truck number must not exceed 50 characters.',
            'truck_number.unique'     => 'This truck number already exists.',
            'status.required'         => 'Status is required.',
        ]);

        $truck->update([
            'transporter_id' => $request->transporter_id,
            'truck_number'   => strtoupper(trim($request->truck_number)),
            'status'         => $request->status,
        ]);

        return response()->json(['success' => true, 'message' => 'Truck updated successfully.']);
    }

    /* ------------------------------------------------------------------ */
    /*  DESTROY                                                             */
    /* ------------------------------------------------------------------ */
    public function destroy(Truck $truck)
    {
        $truck->delete();
        return redirect()->route('truck.index')->with('success', 'Truck deleted successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  BULK DELETE                                                         */
    /* ------------------------------------------------------------------ */
    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        if (! empty($ids)) {
            Truck::whereIn('id', $ids)->delete();
            return response()->json(['message' => 'Selected trucks deleted successfully.']);
        }
        return response()->json(['message' => 'No records selected.'], 400);
    }
}
