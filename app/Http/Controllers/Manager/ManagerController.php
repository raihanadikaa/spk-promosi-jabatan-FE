<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Support\DemoData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagerController extends Controller
{
    public function assessments(Request $request): View
    {
        return view('manager.assessments', [
            'team' => DemoData::subordinatesOf($request->user()->name),
            'period' => DemoData::currentPeriod(),
        ]);
    }

    public function assess(Request $request, int $employee): View
    {
        // Atasan hanya boleh menilai bawahannya sendiri (dokumen ROLE).
        $target = DemoData::subordinatesOf($request->user()->name)[$employee] ?? abort(404);

        return view('manager.assess', [
            'employee' => $target,
            'criteria' => DemoData::criteria(),
            'period' => DemoData::currentPeriod(),
        ]);
    }

    public function ranking(Request $request): View
    {
        $team = DemoData::subordinatesOf($request->user()->name);

        return view('shared.ranking', [
            'ranking' => array_intersect_key(DemoData::ranking(), $team),
            'period' => DemoData::currentPeriod(),
            'detailRoute' => null,
            'scope' => 'team',
        ]);
    }
}
