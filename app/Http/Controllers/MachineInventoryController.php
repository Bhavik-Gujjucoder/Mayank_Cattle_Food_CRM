<?php

namespace App\Http\Controllers;

use App\Models\MachineInventory;
use Illuminate\Http\Request;

class MachineInventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data['page_title'] = "Machine Inventory";
        return view('machine_inventory.index', $data);
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
    public function show(MachineInventory $machineInventory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MachineInventory $machineInventory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MachineInventory $machineInventory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MachineInventory $machineInventory)
    {
        //
    }
}
