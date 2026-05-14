<?php

namespace App\Http\Controllers;

use App\Models\MachineInventory;
use Illuminate\Http\Request;

class MachineInventoryController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  INDEX                                                             */
    /* ------------------------------------------------------------------ */
    public function index()
    {
        $data['page_title'] = "Machine Inventory";
        return view('machine_inventory.index', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  CREATE                                                            */
    /* ------------------------------------------------------------------ */
    public function create()
    {
        //
    }

    /* ------------------------------------------------------------------ */
    /*  STORE                                                             */
    /* ------------------------------------------------------------------ */
    public function store(Request $request)
    {
        //
    }

    /* ------------------------------------------------------------------ */
    /*  SHOW                                                              */
    /* ------------------------------------------------------------------ */
    public function show(MachineInventory $machineInventory)
    {
        //
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT                                                              */
    /* ------------------------------------------------------------------ */
    public function edit(MachineInventory $machineInventory)
    {
        //
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                            */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, MachineInventory $machineInventory)
    {
        //
    }

    /* ------------------------------------------------------------------ */
    /*  DESTROY                                                           */
    /* ------------------------------------------------------------------ */
    public function destroy(MachineInventory $machineInventory)
    {
        //
    }
}
