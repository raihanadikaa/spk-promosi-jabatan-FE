<?php

namespace App\Http\Controllers\Direktur;

use App\Http\Controllers\Controller;
use App\Support\DemoData;
use Illuminate\View\View;

class DirekturController extends Controller
{
    public function ranking(): View
    {
        return view('shared.ranking', [
            'ranking' => DemoData::ranking(),
            'period' => DemoData::currentPeriod(),
            'detailRoute' => 'direktur.ranking.show',
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
            'back' => ['url' => route('direktur.ranking'), 'label' => 'Ranking & rekomendasi'],
            'canDecide' => $row['recommended'],
        ]);
    }
}
