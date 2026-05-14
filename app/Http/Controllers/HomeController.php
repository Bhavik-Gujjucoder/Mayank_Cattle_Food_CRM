<?php

namespace App\Http\Controllers;

use App\Models\DealerManagement;
use App\Models\OrderManagement;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  DASHBOARD                                                         */
    /* ------------------------------------------------------------------ */
    public function dashboard(Request $request)
    {
        $data['login_user']       = auth()->user();
        $data['role']             = $data['login_user']->roles->first()->name;
        $data['user_name']        = $data['login_user']->name;
        $data['page_title']       = ucfirst($data['role']) . ' Dashboard';
        $data['dealers']          = DealerManagement::whereHas('user', function($q) { $q->where('status', 1); })->orderBy('id', 'desc')->get();
        $data['brokers']          = User::whereHas('roles', function($q) { $q->where('name', 'broker'); })->orderBy('id', 'desc')->get();
        $data['transporters']     = User::whereHas('roles', function($q) { $q->where('name', 'transporter'); })->orderBy('id', 'desc')->get();
        $data['soda_order']       = OrderManagement::where('deleted_at', NULL)->get();
        $data['total_dealers']    = $data['dealers']->count();
        $data['total_broker']     = $data['brokers']->count();
        $data['total_soda_order'] = $data['role'] == 'broker' ? $data['soda_order']->where('broker_id', $data['login_user']->id)->count() : $data['soda_order']->count();
        return view('dashboard', $data);
    }

}











