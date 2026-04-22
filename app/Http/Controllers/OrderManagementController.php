<?php

namespace App\Http\Controllers;

use App\Models\OrderManagement;
use Illuminate\Http\Request;

class OrderManagementController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  INDEX                                                             */
    /* ------------------------------------------------------------------ */
    public function index()
    {
        $data['page_title'] = 'Soda/Order Management';
        return view('order_management.index', $data);
    }

    /* ------------------------------------------------------------------ */
    /*  CREATE                                                            */
    /* ------------------------------------------------------------------ */
    public function create()
    {
        $data['page_title'] = 'Add - Soda/Order';
        return view('order_management.create', $data);
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
    public function show(OrderManagement $orderManagement)
    {
        //
    }

    /* ------------------------------------------------------------------ */
    /*  EDIT                                                              */
    /* ------------------------------------------------------------------ */
    public function edit(OrderManagement $orderManagement)
    {
        //
    }

    /* ------------------------------------------------------------------ */
    /*  UPDATE                                                            */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, OrderManagement $orderManagement)
    {
        //
    }

    /* ------------------------------------------------------------------ */
    /*  DESTROY                                                           */
    /* ------------------------------------------------------------------ */
    public function destroy(OrderManagement $orderManagement)
    {
        //
    }
}
