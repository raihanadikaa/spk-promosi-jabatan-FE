<?php

namespace App\Http\Controllers\Direktur;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DirekturDashboardController extends Controller
{
    public function index(): View
    {
        return view('direktur.dashboard');
    }
}