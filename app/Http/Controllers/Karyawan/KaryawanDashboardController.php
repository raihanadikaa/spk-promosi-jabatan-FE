<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class KaryawanDashboardController extends Controller
{
    public function index(): View
    {
        return view('karyawan.dashboard');
    }
}