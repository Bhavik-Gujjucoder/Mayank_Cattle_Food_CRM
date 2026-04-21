<?php

namespace App\Http\Controllers;

use App\Models\OilManagement;
use Illuminate\Http\Request;

class OilManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data['page_title'] = "Oil Management";
        return view('oil_management.index', $data);
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
    public function show(OilManagement $oilManagement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OilManagement $oilManagement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OilManagement $oilManagement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OilManagement $oilManagement)
    {
        //
    }
}
