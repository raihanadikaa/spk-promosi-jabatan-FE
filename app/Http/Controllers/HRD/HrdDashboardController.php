<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HrdDashboardController extends Controller
{
    public function index(): View
    {
        return view('hrd.dashboard');
    }
}