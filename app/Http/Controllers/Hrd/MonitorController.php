<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Concerns\ShowsRanking;
use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentPeriod;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\AssessmentReminder;
use App\Queries\PromotionData;
use App\Support\Format;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Monitor kelengkapan penilaian periode berjalan + pengingat ke atasan (notifikasi di aplikasi).
 */
class MonitorController extends Controller
{
    use ShowsRanking;

    public function index(Request $request): View
    {
        $period = PromotionData::currentPeriod();
        if (! $period) {
            return $this->noPeriodView();
        }

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'department' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:pending,draft,submitted,unfinished'],
        ]);
        $filters = [
            'q' => $validated['q'] ?? null,
            'department_id' => isset($validated['department']) ? (int) $validated['department'] : null,
            'status' => $validated['status'] ?? null,
        ];

        return view('hrd.monitor', [
            'period' => $period,
            'counts' => PromotionData::statusCounts($period),
            'employees' => PromotionData::employeesPage($period, $filters),
            'filters' => $filters,
            'departments' => PromotionData::departments(),
            'canRemind' => PromotionData::isOpen($period),
            'withoutSupervisor' => Employee::active()->whereNull('supervisor_id')->count(),
        ]);
    }

    /** Ingatkan atasan dari satu karyawan (semua bawahannya yang belum terkirim ikut disebut). */
    public function remind(Employee $employee): RedirectResponse
    {
        if (! $employee->supervisor_id) {
            return back()->withErrors(['monitor' => "{$employee->name} belum punya atasan penilai. Atur dulu di halaman Karyawan."]);
        }

        $sent = $this->sendReminders([$employee->supervisor_id]);

        return back()->with('success', $sent ? 'Pengingat terkirim ke ' . $employee->supervisor()->value('name') . '.' : 'Semua penilaian bawahan atasan ini sudah terkirim.');
    }

    /** Ingatkan semua atasan yang masih punya penilaian belum terkirim. */
    public function remindAll(): RedirectResponse
    {
        $sent = $this->sendReminders(null);

        return back()->with('success', $sent ? "Pengingat terkirim ke {$sent} atasan." : 'Tidak ada atasan yang perlu diingatkan.');
    }

    /**
     * @param  list<int>|null  $supervisorIds  null = semua atasan
     * @return int jumlah atasan yang diberi notifikasi
     */
    private function sendReminders(?array $supervisorIds): int
    {
        $period = PromotionData::currentPeriodModel();
        if (! $period || $period->status !== AssessmentPeriod::ONGOING) {
            throw ValidationException::withMessages(['monitor' => 'Pengingat hanya bisa dikirim saat periode sedang berlangsung.']);
        }

        $unfinished = Employee::active()
            ->whereNotNull('supervisor_id')
            ->when($supervisorIds, fn ($q) => $q->whereIn('supervisor_id', $supervisorIds))
            ->whereDoesntHave('assessments', fn ($a) => $a->where('period_id', $period->id)->where('status', Assessment::SUBMITTED))
            ->orderBy('name')
            ->get(['id', 'name', 'supervisor_id'])
            ->groupBy('supervisor_id');

        $managers = User::whereIn('id', $unfinished->keys())->where('is_active', true)->get();
        foreach ($managers as $manager) {
            $manager->notify(new AssessmentReminder(
                $period->period_name,
                Format::date($period->assessment_deadline->toDateString()),
                $unfinished[$manager->id]->pluck('name')->all(),
            ));
        }

        return $managers->count();
    }
}
