<?php

namespace App\Http\Controllers;

use App\Models\DispatchManagement;
use Illuminate\Http\Request;

class DispatchManagementController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  INDEX                                                             */
    /* ------------------------------------------------------------------ */
    public function index()
    {
        $data['page_title'] = 'Dispatch Management';
        return view('dispatch_management.index', $data);
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
    public function show(DispatchManagement $dispatchManagement)
    {
        //
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT                                                              */
    /* ------------------------------------------------------------------ */
    public function edit(DispatchManagement $dispatchManagement)
    {
        //
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                            */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, DispatchManagement $dispatchManagement)
    {
        //
    }

    /* ------------------------------------------------------------------ */
    /*  DESTROY                                                           */
    /* ------------------------------------------------------------------ */
    public function destroy(DispatchManagement $dispatchManagement)
    {
        //
    }
}
