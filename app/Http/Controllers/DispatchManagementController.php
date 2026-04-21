<?php

namespace App\Http\Controllers;

use App\Models\DispatchManagement;
use Illuminate\Http\Request;

class DispatchManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data['page_title'] = 'Dispatch Management';
        return view('dispatch_management.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(DispatchManagement $dispatchManagement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DispatchManagement $dispatchManagement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DispatchManagement $dispatchManagement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DispatchManagement $dispatchManagement)
    {
        //
    }
}
