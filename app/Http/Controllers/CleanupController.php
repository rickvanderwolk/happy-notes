<?php

namespace App\Http\Controllers;

final class CleanupController extends Controller
{
    public function index(): \Illuminate\View\View|\Illuminate\Contracts\View\View
    {
        return view('cleanup');
    }
}
