<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Support\DemoData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrdController extends Controller
{
    public function employees(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'string', 'in:' . implode(',', DemoData::departments())],
        ]);

        $q = mb_strtolower($validated['q'] ?? '');
        $department = $validated['department'] ?? null;

        $employees = array_filter(DemoData::employees(), fn ($e) =>
            ($q === '' || str_contains(mb_strtolower($e['name'] . ' ' . $e['nik']), $q))
            && (! $department || $e['department'] === $department)
        );

        return view('hrd.employees', [
            'employees' => $employees,
            'departments' => DemoData::departments(),
            'positions' => DemoData::positions(),
            'q' => $validated['q'] ?? '',
            'department' => $department,
        ]);
    }

    public function periods(): View
    {
        return view('hrd.periods', [
            'periods' => DemoData::periods(),
            'employees' => DemoData::employees(),
        ]);
    }

    public function criteria(): View
    {
        return view('hrd.criteria', [
            'criteria' => DemoData::criteria(),
            'period' => DemoData::currentPeriod(),
        ]);
    }

    public function saw(): View
    {
        return view('hrd.saw', [
            'criteria' => DemoData::criteria(),
            'ranking' => DemoData::ranking(),
            'employees' => DemoData::employees(),
            'period' => DemoData::currentPeriod(),
        ]);
    }

    public function ranking(): View
    {
        return view('shared.ranking', [
            'ranking' => DemoData::ranking(),
            'period' => DemoData::currentPeriod(),
            'detailRoute' => 'hrd.ranking.show',
            'scope' => 'all',
        ]);
    }

    public function candidate(int $employee): View
    {
        $row = DemoData::ranking()[$employee] ?? abort(404);

        return view('shared.candidate', [
            'row' => $row,
            'criteria' => DemoData::criteria(),
            'total' => count(DemoData::ranking()),
            'back' => ['url' => route('hrd.ranking'), 'label' => 'Ranking'],
            'canDecide' => false,
        ]);
    }
}
