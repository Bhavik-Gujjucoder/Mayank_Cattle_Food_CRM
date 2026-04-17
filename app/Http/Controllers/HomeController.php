<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function dashboard(Request $request)
    {
        $data['page_title'] = 'Admin Dashboard';
        return view('dashboard', $data);
    }
}
