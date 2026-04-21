<?php

namespace App\Http\Controllers;

use App\Models\OrderManagement;
use Illuminate\Http\Request;

class OrderManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data['page_title'] = 'Soda/Order Management';
        return view('order_management.index', $data);
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
    public function show(OrderManagement $orderManagement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(OrderManagement $orderManagement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OrderManagement $orderManagement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderManagement $orderManagement)
    {
        //
    }
}
