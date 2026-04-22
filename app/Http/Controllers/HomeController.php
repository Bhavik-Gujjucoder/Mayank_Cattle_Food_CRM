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
        $data['login_user'] = auth()->user();
        $data['role'] = $data['login_user']->roles->first()->name;
        $data['user_name'] = $data['login_user']->name;
        $data['page_title'] = ucfirst($data['role']) . ' Dashboard';
        $data['dealers'] = DealerManagement::whereHas('user', function($q) { $q->where('status', 1); })->orderBy('id', 'desc')->get();
        $data['total_dealers'] =  $data['dealers']->count();
        $data['brokers'] = User::whereHas('roles', function($q) { $q->where('name', 'broker'); })->orderBy('id', 'desc')->get();
        $data['total_broker'] =  $data['brokers']->count();
        $data['transporters'] = User::whereHas('roles', function($q) { $q->where('name', 'transporter'); })->orderBy('id', 'desc')->get();

        return view('dashboard', $data);
    }

}
