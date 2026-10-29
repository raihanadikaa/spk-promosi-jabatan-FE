<?php

namespace App\Queries;

use App\Models\Assessment;
use App\Models\AssessmentDetail;
use App\Models\AssessmentPeriod;
use App\Models\CriteriaWeight;
use App\Models\Criterion;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\RankingResult;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Lapisan baca untuk view: mengubah data Eloquent menjadi array siap tampil.
 *
 * - Daftar besar (karyawan, ranking) difilter dan dipaginasi di database, bukan di PHP.
 * - Relasi di-eager-load; tidak ada query per baris (anti N+1).
 * - Data referensi (kriteria, periode) di-memo per request karena dipanggil berulang oleh komponen.
 * - Bentuk array dijaga stabil supaya tampilan bisa diganti tanpa menyentuh query.
 */
class PromotionData
{
    /** Jumlah kandidat teratas yang direkomendasikan untuk promosi. */
    public const RECOMMENDED_COUNT = 3;

    public const PER_PAGE = 15;

    private static array $memo = [];

    public static function flush(): void
    {
        self::$memo = [];
    }

    private static function memo(string $key, callable $fn): mixed
    {
        return array_key_exists($key, self::$memo) ? self::$memo[$key] : (self::$memo[$key] = $fn());
    }

    /** Escape karakter wildcard LIKE supaya input "%" atau "_" dicari apa adanya. */
    private static function like(string $term): string
    {
        return '%' . addcslashes($term, '%_\\') . '%';
    }

    /* ================= Referensi ================= */

    public static function roleLabels(): array
    {
        return [
            'admin' => 'Admin',
            'hrd' => 'HRD',
            'manager' => 'Atasan',
            'direktur' => 'Direktur',
            'karyawan' => 'Karyawan',
        ];
    }

    /** @return array<int, string> [id => nama] */
    public static function departments(): array
    {
        return self::memo('departments', fn () => Department::orderBy('department_name')->pluck('department_name', 'id')->all());
    }

    public static function positions(): array
    {
        return self::memo('positions', fn () => Position::orderBy('level')->get(['id', 'position_name', 'level'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->position_name, 'level' => $p->level])->all());
    }

    /** @return Collection<int, Criterion> */
    private static function criteriaModels(): Collection
    {
        return self::memo('criteria.models', fn () => Criterion::orderBy('sort_order')->get());
    }

    /** @return array<int, string> [id => kode] */
    private static function criteriaCodes(): array
    {
        return self::criteriaModels()->pluck('code', 'id')->all();
    }

    /** Peta kode kriteria → nama, untuk label bar skor. */
    public static function criteriaNames(): array
    {
        return self::criteriaModels()->pluck('criteria_name', 'code')->all();
    }

    /**
     * Kriteria + bobot periode (tanpa periode: bobot 0).
     *
     * @return list<array{id:int, code:string, name:string, weight:int, type:string, input:string, unit:string, min:?int, max:?int, help:string}>
     */
    public static function criteria(?int $periodId = null): array
    {
        $periodId ??= self::currentPeriodModel()?->id;

        return self::memo("criteria.$periodId", function () use ($periodId) {
            $weights = $periodId ? CriteriaWeight::where('period_id', $periodId)->pluck('weight_value', 'criteria_id') : collect();

            return self::criteriaModels()->map(fn (Criterion $c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->criteria_name,
                'weight' => (int) ($weights[$c->id] ?? 0),
                'type' => $c->attribute_type,
                'input' => $c->input_type,
                'unit' => $c->unit,
                'min' => $c->min_value,
                'max' => $c->max_value,
                'help' => $c->description,
            ])->all();
        });
    }

    public static function weightTotal(?int $periodId = null): int
    {
        return array_sum(array_column(self::criteria($periodId), 'weight'));
    }

    /* ================= Periode ================= */

    public static function periodArray(AssessmentPeriod $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->period_name,
            'start' => $p->start_date->toDateString(),
            'end' => $p->end_date->toDateString(),
            'deadline' => $p->assessment_deadline->toDateString(),
            'status' => $p->status,
            'calculated_at' => $p->calculated_at?->format('Y-m-d H:i'),
            'calculated_at_raw' => $p->calculated_at?->toDateTimeString(),
        ];
    }

    public static function currentPeriodModel(): ?AssessmentPeriod
    {
        return self::memo('current.model', fn () => AssessmentPeriod::current());
    }

    /** Periode berjalan sebagai array, atau null jika belum ada periode sama sekali. */
    public static function currentPeriod(): ?array
    {
        $p = self::currentPeriodModel();

        return $p ? self::periodArray($p) : null;
    }

    /**
     * Periode yang bobotnya sedang diatur HRD: yang berlangsung, atau draft terbaru
     * (supaya bobot bisa disiapkan sebelum periode dimulai), atau periode terakhir.
     */
    public static function weightsPeriodModel(): ?AssessmentPeriod
    {
        return self::memo('weights.model', fn () => AssessmentPeriod::ongoing()->first()
            ?? AssessmentPeriod::where('status', AssessmentPeriod::DRAFT)->orderByDesc('start_date')->first()
            ?? self::currentPeriodModel());
    }

    public static function periods(): array
    {
        return self::memo('periods', fn () => AssessmentPeriod::orderByDesc('start_date')->get()
            ->map(fn ($p) => self::periodArray($p))->keyBy('id')->all());
    }

    public static function isOpen(?array $period = null): bool
    {
        return (($period ?? self::currentPeriod())['status'] ?? null) === AssessmentPeriod::ONGOING;
    }

    private static function previousPeriodId(array $period): ?int
    {
        return self::memo("previous.{$period['id']}", fn () => AssessmentPeriod::query()
            ->where('start_date', '<', $period['start'])
            ->where('status', '!=', AssessmentPeriod::DRAFT)
            ->orderByDesc('start_date')
            ->value('id'));
    }

    /* ================= Karyawan & penilaian ================= */

    /**
     * Query karyawan + status penilaian di satu periode.
     *
     * @param  array{q?:?string, department_id?:?int, status?:?string, supervisor_id?:?int, with_inactive?:bool}  $filters
     */
    public static function employeeQuery(?array $period, array $filters = []): Builder
    {
        $periodId = $period['id'] ?? 0;

        return Employee::query()
            ->when(empty($filters['with_inactive']), fn ($q) => $q->where('is_active', true))
            ->when($filters['supervisor_id'] ?? null, fn ($q, $id) => $q->where('supervisor_id', $id))
            ->when($filters['department_id'] ?? null, fn ($q, $id) => $q->where('department_id', $id))
            ->when(filled($filters['q'] ?? null), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', self::like($filters['q']))
                ->orWhere('nik', 'like', self::like($filters['q']))))
            ->when($filters['status'] ?? null, fn ($q, $status) => match ($status) {
                'pending' => $q->whereDoesntHave('assessments', fn ($a) => $a->where('period_id', $periodId)),
                // belum selesai = belum dinilai atau masih draft
                'unfinished' => $q->whereDoesntHave('assessments', fn ($a) => $a->where('period_id', $periodId)->where('status', Assessment::SUBMITTED)),
                default => $q->whereHas('assessments', fn ($a) => $a->where('period_id', $periodId)->where('status', $status)),
            })
            ->with([
                'department:id,department_name',
                'position:id,position_name,level',
                'supervisor:id,name',
                'assessments' => fn ($q) => $q->where('period_id', $periodId)->with('details:id,assessment_id,criteria_id,raw_score'),
            ])
            ->orderBy('name')
            ->orderBy('id');
    }

    public static function employeeArray(Employee $e, ?array $period): array
    {
        $codes = self::criteriaCodes();
        $end = $period ? CarbonImmutable::parse($period['end']) : now()->toImmutable();
        $a = $e->assessments->first();
        $raw = $a ? $a->details->mapWithKeys(fn ($d) => [$codes[$d->criteria_id] => $d->raw_score])->sortKeys()->all() : null;

        return [
            'id' => $e->id,
            'name' => $e->name,
            'nik' => $e->nik,
            'department' => $e->department->department_name,
            'department_id' => $e->department_id,
            'position' => $e->position->position_name,
            'position_id' => $e->position_id,
            'level' => $e->position->level,
            'join_date' => $e->join_date->toDateString(),
            'tenure' => $e->tenureAt($end),
            'assessor' => $e->supervisor?->name ?? 'Belum ditentukan',
            'supervisor_id' => $e->supervisor_id,
            'user_id' => $e->user_id,
            'is_active' => $e->is_active,
            'assessment_id' => $a?->id,
            'status' => $a?->status ?? 'pending',
            'note' => $a?->notes,
            'updated_at' => $a?->updated_at?->format('Y-m-d H:i'),
            'submitted_at' => $a?->submitted_at?->format('Y-m-d H:i'),
            'raw' => $raw,
        ];
    }

    /** Semua karyawan yang cocok (tanpa paginasi) — untuk daftar kecil seperti tim satu atasan. */
    public static function employees(?array $period = null, array $filters = []): array
    {
        $period ??= self::currentPeriod();

        return self::memo('employees.' . ($period['id'] ?? 0) . '.' . md5(serialize($filters)), fn () => self::employeeQuery($period, $filters)->get()
            ->mapWithKeys(fn ($e) => [$e->id => self::employeeArray($e, $period)])->all());
    }

    public static function employeesPage(?array $period, array $filters = [], int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        return self::employeeQuery($period, $filters)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn ($e) => self::employeeArray($e, $period));
    }

    public static function subordinatesOf(\App\Models\User $user): array
    {
        return self::employees(null, ['supervisor_id' => $user->id]);
    }

    /**
     * Jumlah karyawan aktif per status penilaian di satu periode.
     *
     * @return array{total:int, submitted:int, draft:int, pending:int}
     */
    public static function statusCounts(?array $period, ?int $supervisorId = null): array
    {
        $periodId = $period['id'] ?? 0;

        return self::memo("counts.$periodId.$supervisorId", function () use ($periodId, $supervisorId) {
            $active = Employee::where('is_active', true)->when($supervisorId, fn ($q) => $q->where('supervisor_id', $supervisorId));
            $total = (clone $active)->count();
            $byStatus = Assessment::where('period_id', $periodId)
                ->whereIn('employee_id', (clone $active)->select('id'))
                ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

            $submitted = (int) ($byStatus['submitted'] ?? 0);
            $draft = (int) ($byStatus['draft'] ?? 0);

            return ['total' => $total, 'submitted' => $submitted, 'draft' => $draft, 'pending' => max(0, $total - $submitted - $draft)];
        });
    }

    /** Progres penilaian per departemen (diagregasi di database). */
    public static function departmentProgress(?array $period): array
    {
        $periodId = $period['id'] ?? 0;

        return Employee::query()
            ->where('employees.is_active', true)
            ->join('departments', 'departments.id', '=', 'employees.department_id')
            ->leftJoin('assessments', fn ($j) => $j->on('assessments.employee_id', '=', 'employees.id')->where('assessments.period_id', $periodId))
            ->groupBy('departments.id', 'departments.department_name')
            ->orderBy('departments.department_name')
            ->selectRaw("departments.department_name as department, count(*) as total,
                sum(case when assessments.status = 'submitted' then 1 else 0 end) as submitted,
                sum(case when assessments.status = 'draft' then 1 else 0 end) as draft")
            ->get()
            ->map(fn ($r) => ['department' => $r->department, 'total' => (int) $r->total, 'submitted' => (int) $r->submitted, 'draft' => (int) $r->draft])
            ->all();
    }

    /* ================= Ranking ================= */

    /**
     * @param  array{q?:?string, department_id?:?int, status?:?string, employee_ids?:array}  $filters
     *   status: recommended | pending | approved | rejected | not_recommended
     */
    public static function rankingQuery(array $period, array $filters = []): Builder
    {
        return RankingResult::query()
            ->where('period_id', $period['id'])
            ->when(isset($filters['employee_ids']), fn ($q) => $q->whereIn('employee_id', $filters['employee_ids']))
            ->when(filled($filters['q'] ?? null) || ($filters['department_id'] ?? null), fn ($q) => $q->whereHas('employee', fn ($e) => $e
                ->when(filled($filters['q'] ?? null), fn ($w) => $w->where(fn ($x) => $x
                    ->where('name', 'like', self::like($filters['q']))
                    ->orWhere('nik', 'like', self::like($filters['q']))))
                ->when($filters['department_id'] ?? null, fn ($w, $id) => $w->where('department_id', $id))))
            ->when($filters['status'] ?? null, fn ($q, $status) => match ($status) {
                'recommended' => $q->whereNotNull('approval_status'),
                'not_recommended' => $q->whereNull('approval_status'),
                default => $q->where('approval_status', $status),
            })
            ->orderBy('rank')
            ->orderBy('employee_id');
    }

    public static function rankingPage(array $period, array $filters = [], int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $page = self::rankingQuery($period, $filters)->paginate($perPage)->withQueryString();
        $rows = self::rankingRows($page->getCollection(), $period);

        return $page->setCollection(collect($rows));
    }

    /** Semua baris ranking yang cocok (untuk daftar kecil: dashboard, kandidat direkomendasikan). */
    public static function ranking(?array $period = null, array $filters = [], ?int $limit = null): array
    {
        $period ??= self::currentPeriod();
        if (! $period) {
            return [];
        }

        return self::memo("ranking.{$period['id']}." . md5(serialize($filters)) . ".$limit", fn () => self::rankingRows(
            self::rankingQuery($period, $filters)->when($limit, fn ($q) => $q->limit($limit))->get(),
            $period,
        ));
    }

    public static function rankingRow(?array $period, int $employeeId): ?array
    {
        if (! $period) {
            return null;
        }

        return self::ranking($period, ['employee_ids' => [$employeeId]])[$employeeId] ?? null;
    }

    /**
     * Jumlah kandidat per status keputusan.
     *
     * @return array{recommended:int, pending:int, approved:int, rejected:int}
     */
    public static function approvalCounts(?array $period): array
    {
        $counts = $period
            ? RankingResult::where('period_id', $period['id'])->whereNotNull('approval_status')
                ->selectRaw('approval_status, count(*) as n')->groupBy('approval_status')->pluck('n', 'approval_status')
            : collect();

        return [
            'recommended' => (int) $counts->sum(),
            'pending' => (int) ($counts['pending'] ?? 0),
            'approved' => (int) ($counts['approved'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
        ];
    }

    public static function rankingCount(?array $period): int
    {
        return $period ? self::memo("ranking.count.{$period['id']}", fn () => RankingResult::where('period_id', $period['id'])->count()) : 0;
    }

    /**
     * Ubah hasil ranking menjadi baris lengkap: identitas, nilai mentah, normalisasi, kontribusi, keputusan,
     * dan skor periode lalu. Jumlah query tetap (tidak tergantung jumlah baris).
     *
     * @param  Collection<int, RankingResult>  $results
     */
    public static function rankingRows(Collection $results, array $period): array
    {
        if ($results->isEmpty()) {
            return [];
        }

        $ids = $results->pluck('employee_id')->all();
        $codes = self::criteriaCodes();
        $end = CarbonImmutable::parse($period['end']);

        $employees = Employee::whereIn('id', $ids)
            ->with(['department:id,department_name', 'position:id,position_name,level', 'supervisor:id,name'])
            ->get()->keyBy('id');

        $assessments = Assessment::where('period_id', $period['id'])
            ->whereIn('employee_id', $ids)
            ->with('details.normalized')
            ->get()->keyBy('employee_id');

        $approvers = \App\Models\User::whereIn('id', $results->pluck('approved_by')->filter()->unique())->pluck('name', 'id');

        $previousId = self::previousPeriodId($period);
        $previous = $previousId
            ? RankingResult::where('period_id', $previousId)->whereIn('employee_id', $ids)->pluck('final_score', 'employee_id')
            : collect();

        $rows = [];
        foreach ($results as $r) {
            $e = $employees[$r->employee_id] ?? null;
            if (! $e) {
                continue;
            }
            $a = $assessments[$r->employee_id] ?? null;

            $raw = $normalized = $contributions = [];
            foreach (($a?->details ?? collect())->sortBy(fn ($d) => $codes[$d->criteria_id]) as $d) {
                $code = $codes[$d->criteria_id];
                $raw[$code] = $d->raw_score;
                $normalized[$code] = $d->normalized?->normalized_value ?? 0.0;
                $contributions[$code] = $d->normalized?->weighted_value ?? 0.0;
            }

            $rows[$r->employee_id] = [
                'id' => $e->id,
                'name' => $e->name,
                'nik' => $e->nik,
                'department' => $e->department->department_name,
                'position' => $e->position->position_name,
                'join_date' => $e->join_date->toDateString(),
                'tenure' => $e->tenureAt($end),
                'assessor' => $e->supervisor?->name ?? '–',
                'submitted_at' => $a?->submitted_at?->format('Y-m-d H:i'),
                'raw' => $raw,
                'normalized' => $normalized,
                'contributions' => $contributions,
                'score' => $r->final_score,
                'rank' => $r->rank,
                'recommended' => $r->approval_status !== null,
                'approval_status' => $r->approval_status,
                'approval_at' => $r->approved_at?->format('Y-m-d H:i'),
                'approval_note' => $r->approval_note,
                'approval_by' => $r->approved_by ? ($approvers[$r->approved_by] ?? null) : null,
                'previous_score' => isset($previous[$r->employee_id]) ? (float) $previous[$r->employee_id] : null,
            ];
        }

        return $rows;
    }

    /**
     * Acuan normalisasi tiap kriteria (max untuk benefit, min untuk cost) dari semua peserta ranking,
     * dihitung di database.
     *
     * @return array<string, float>
     */
    public static function bounds(array $period): array
    {
        return self::memo("bounds.{$period['id']}", function () use ($period) {
            $codes = self::criteriaCodes();
            $types = self::criteriaModels()->pluck('attribute_type', 'id');

            return AssessmentDetail::query()
                ->join('assessments', 'assessments.id', '=', 'assessment_details.assessment_id')
                ->join('ranking_results', fn ($j) => $j->on('ranking_results.employee_id', '=', 'assessments.employee_id')
                    ->on('ranking_results.period_id', '=', 'assessments.period_id'))
                ->where('assessments.period_id', $period['id'])
                ->groupBy('assessment_details.criteria_id')
                ->selectRaw('assessment_details.criteria_id, min(raw_score) as lo, max(raw_score) as hi')
                ->get()
                ->mapWithKeys(fn ($r) => [$codes[$r->criteria_id] => (float) ($types[$r->criteria_id] === 'cost' ? $r->lo : $r->hi)])
                ->all();
        });
    }

    /**
     * Alasan ranking perlu dihitung ulang. Kosong = ranking sudah sesuai data terbaru.
     */
    public static function staleReasons(?array $period = null): array
    {
        $period ??= self::currentPeriod();
        if (! $period || $period['status'] !== AssessmentPeriod::ONGOING) {
            return [];
        }

        return self::memo("stale.{$period['id']}", function () use ($period) {
            $calculatedAt = $period['calculated_at_raw'];
            $submitted = Assessment::where('period_id', $period['id'])->where('status', Assessment::SUBMITTED);
            if (! $calculatedAt) {
                return (clone $submitted)->exists() ? ['Ranking periode ini belum pernah dihitung.'] : [];
            }

            $reasons = [];
            $new = (clone $submitted)->where('submitted_at', '>', $calculatedAt)->count();
            if ($new > 0) {
                $reasons[] = "$new penilaian baru masuk setelah perhitungan terakhir.";
            }
            if (CriteriaWeight::where('period_id', $period['id'])->where('updated_at', '>', $calculatedAt)->exists()) {
                $reasons[] = 'Bobot atau jenis kriteria diubah setelah perhitungan terakhir.';
            }

            return $reasons;
        });
    }
}
