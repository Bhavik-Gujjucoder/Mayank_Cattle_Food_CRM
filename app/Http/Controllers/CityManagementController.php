<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CityManagement;
use App\Models\StateManagement;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;
use NunoMaduro\Collision\Adapters\Phpunit\State;

class CityManagementController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  INDEX                                                               */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $data['page_title'] = 'City Management';
        $data['states'] = StateManagement::where('status', 1)->get();
        if ($request->ajax()) {
            $data = CityManagement::query();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($row) {
                    return '<label class="checkboxs">
                            <input type="checkbox" class="checkbox-item city_checkbox" data-id="' . $row->id . '">
                            <span class="checkmarks"></span>
                        </label>';
                })
                ->addColumn('action', function ($row) {
                    $edit_btn = '<a href="javascript:void(0)" class="dropdown-item edit-btn"  data-id="' . $row->id . '"
                    class="btn btn-outline-warning btn-sm edit-btn"><i class="ti ti-edit text-warning"></i>Edit</a>';

                    $delete_btn = '<a href="javascript:void(0)" class="dropdown-item deleteCity"  data-id="' . $row->id . '"
                    class="btn btn-outline-warning btn-sm edit-btn"> <i class="ti ti-trash text-danger"></i> ' . __('Delete') . '</a><form action="' . route('city.destroy', $row->id) . '" method="post" class="delete-form" id="delete-form-' . $row->id . '" >'
                        . csrf_field() . method_field('DELETE') . '</form>';

                    $action_btn = '<div class="dropdown table-action">
                                             <a href="#" class="action-icon" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-ellipsis-v"></i></a>
                                             <div class="dropdown-menu dropdown-menu-right">';

                    $action_btn .= auth()->user()->can('edit-city') ? $edit_btn : '';
                    $action_btn .= auth()->user()->can('delete-city') ? $delete_btn : '';

                    return $action_btn . ' </div></div>';
                })
                ->editColumn('status', function ($row) {
                    return $row->statusBadge();
                })
                ->editColumn('state_id', function ($row) {
                    return $row->state->state_name;
                })
                ->filterColumn('state_id', function ($query, $keyword) {
                    $query->whereHas('state', function ($q) use ($keyword) {
                        $q->where('state_name', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['action', 'status', 'checkbox'])
                ->make(true);
        }
        return view('city.index', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  STORE                                                               */
    /* ------------------------------------------------------------------ */
    public function store(Request $request)
    {
        $request->validate([
            'state_id'  => 'required',
            'city_name' => 'required|unique:city_management,city_name,NULL,id,deleted_at,NULL'
        ], [
            'state_id.required' => 'The state name field is required.'
        ]);
        CityManagement::create([
            'state_id'  => $request->state_id,
            'city_name' => $request->city_name,
            'status'    => $request->status
        ]);
        return response()->json(['success' => true, 'message' => 'City created successfully']);
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT                                                              */
    /* ------------------------------------------------------------------ */
    public function edit(CityManagement $city)
    {
        return response()->json($city);
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                              */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, CityManagement $city)
    {
        $request->validate([
            'state_id'  => 'required',
            'city_name' => 'required|unique:city_management,city_name,' . $city->id . ',id,deleted_at,NULL'
        ], [
            'state_id.required' => 'The state name field is required.'
        ]);
        $city->update([
            'state_id'  => $request->state_id,
            'city_name' => $request->city_name,
            'status'    => $request->status
        ]);
        return response()->json(['success' => true, 'message' => 'City updated successfully']);
    }

    /* ------------------------------------------------------------------ */
    /*  DESTROY                                                              */
    /* ------------------------------------------------------------------ */
    public function destroy(CityManagement $city)
    {
        $city->delete();
        return redirect()->route('city.index')->with('success', 'City deleted successfully.');
    }

    /* ------------------------------------------------------------------ */
    /*  BULK DELETE                                                       */
    /* ------------------------------------------------------------------ */
    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;

        if (!empty($ids)) {
            CityManagement::whereIn('id', $ids)->delete();
            return response()->json(['message' => 'Selected City deleted successfully!']);
        }

        return response()->json(['message' => 'No records selected!'], 400);
    }
}
