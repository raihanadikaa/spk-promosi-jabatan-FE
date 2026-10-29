<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DemoData;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function users(): View
    {
        return view('admin.users', [
            'users' => DemoData::users(),
            'roles' => DemoData::roleLabels(),
        ]);
    }
}
