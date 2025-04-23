<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index');
    }

    public function dataEntry()
    {
        return view('dashboard.data-entry');
    }

    public function store(Request $request)
    {
        return redirect()->back()->with('success', 'Lecture saved!');
    }
}
