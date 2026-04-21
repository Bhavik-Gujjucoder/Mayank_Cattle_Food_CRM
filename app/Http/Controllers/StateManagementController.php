<?php

namespace App\Http\Controllers;

use App\Models\CityManagement;
use Illuminate\Http\Request;
use App\Models\StateManagement;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;

class StateManagementController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  INDEX                                                             */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $data['page_title'] = 'State Management';

        if ($request->ajax()) {
            // $data = StateManagement::withCount('cities');
            $data = StateManagement::withCount([
                'cities as active_cities_count' => function ($query) {
                    $query->where('status', 1);
                }
            ]);
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<label class="checkboxs">
                            <input type="checkbox" class="checkbox-item state_checkbox" data-id="' . $row->id . '">
                            <span class="checkmarks"></span>
                        </label>';
                })
                ->addColumn('action', function ($row) {
                    $edit_btn = '<a href="javascript:void(0)" class="dropdown-item edit-btn"  data-id="' . $row->id . '"
                    class="btn btn-outline-warning btn-sm edit-btn"><i class="ti ti-edit text-warning"></i>Edit</a>';

                    $delete_btn = '<a href="javascript:void(0)" class="dropdown-item deleteState"  data-id="' . $row->id . '"
                    class="btn btn-outline-warning btn-sm edit-btn"> <i class="ti ti-trash text-danger"></i> ' . __('Delete') . '</a><form action="' . route('state.destroy', $row->id) . '" method="post" class="delete-form" id="delete-form-' . $row->id . '" >'
                        . csrf_field() . method_field('DELETE') . '</form>';

                    $action_btn = '<div class="dropdown table-action">
                                             <a href="#" class="action-icon " data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                                             <div class="dropdown-menu dropdown-menu-right">';
                    // Auth::user()->can('manage users') ? $action_btn .= $edit_btn : '';
                    // Auth::user()->can('manage users') ? $action_btn .= $delete_btn : '';
                    $action_btn .= $edit_btn;
                    $action_btn .= $delete_btn;
                    return $action_btn . ' </div></div>';
                })

                ->addColumn('active_cities_count', function ($row) {
                    return $row->active_cities_count;   /*withCount('cities') => cities_count */
                })
                ->orderColumn('cities_count', function ($query, $order) {
                    $query->orderBy('cities_count', $order);
                })

                ->editColumn('status', function ($row) {
                    return $row->statusBadge(); // Get user roles
                })

                ->rawColumns(['action', 'status', 'checkbox', 'number_of_state_city'])
                ->make(true);
        }
        return view('state.index', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  STORE                                                             */
    /* ------------------------------------------------------------------ */
    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate(['state_name' => 'required|unique:state_management,state_name,NULL,id,deleted_at,NULL']);
        StateManagement::create([
            'state_name' => $request->state_name,
            'status'     => $request->status
        ]);
        return response()->json(['success' => true, 'message' => 'State created successfully']);
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT                                                              */
    /* ------------------------------------------------------------------ */
    public function edit(StateManagement $state)
    {
        return response()->json($state);
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                            */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, StateManagement $state)
    {
        $request->validate(['state_name' => 'required|unique:state_management,state_name,' . $state->id . ',id,deleted_at,NULL']);
        $state->update([
            'state_name' => $request->state_name,
            'status' => $request->status
        ]);

        return response()->json(['success' => true, 'message' => 'State updated successfully']);
    }

    /* ------------------------------------------------------------------ */
    /*  DESTROY                                                           */
    /* ------------------------------------------------------------------ */
    public function destroy(StateManagement $state)
    {
        $cities = CityManagement::where('state_id', $state->id)->delete();
        $state->delete();
        return redirect()->route('state.index')->with('success', 'State deleted successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  BULK DELETE                                                       */
    /* ------------------------------------------------------------------ */
    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;

        if (!empty($ids)) {
            CityManagement::whereIn('state_id', $ids)->delete();
            StateManagement::whereIn('id', $ids)->delete();
            return response()->json(['message' => 'Selected states deleted successfully!']);
        }

        return response()->json(['message' => 'No records selected!'], 400);
    }
}
