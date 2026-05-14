<?php

namespace App\Http\Controllers;

use App\Models\OilManagement;
use Illuminate\Http\Request;

class OilManagementController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  INDEX                                                             */
    /* ------------------------------------------------------------------ */
    public function index()
    {
        $data['page_title'] = "Oil Management";
        return view('oil_management.index', $data);
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
    public function show(OilManagement $oilManagement)
    {
        //
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT                                                              */
    /* ------------------------------------------------------------------ */
    public function edit(OilManagement $oilManagement)
    {
        //
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                            */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, OilManagement $oilManagement)
    {
        //
    }

    /* ------------------------------------------------------------------ */
    /*  DESTROY                                                           */
    /* ------------------------------------------------------------------ */
    public function destroy(OilManagement $oilManagement)
    {
        //
    }
}
