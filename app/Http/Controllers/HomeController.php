<?php

namespace App\Http\Controllers;

use App\Models\DealerManagement;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  DASHBOARD                                                         */
    /* ------------------------------------------------------------------ */
    public function dashboard(Request $request)
    {
        $data['page_title'] = 'Admin Dashboard';
        $data['dealers'] = DealerManagement::whereHas('user', function($q) { $q->where('status', 1); })->orderBy('id', 'desc')->get();
        $data['total_dealers'] =  $data['dealers']->count();


        $data['brokers'] = User::whereHas('roles', function($q) { $q->where('name', 'broker'); })->orderBy('id', 'desc')->get();
        $data['total_broker'] =  $data['brokers']->count();

        $data['transporters'] = User::whereHas('roles', function($q) { $q->where('name', 'transporter'); })->orderBy('id', 'desc')->get();

        return view('dashboard', $data);
    }
}
