<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Impor karyawan dari CSV (pemisah ; atau ,). Semua baris harus valid: satu saja salah, tidak ada yang disimpan,
 * supaya HRD tidak perlu menebak baris mana yang sudah masuk.
 *
 * Kolom: nama; nik; departemen; jabatan; tanggal_bergabung (YYYY-MM-DD atau DD-MM-YYYY); email_atasan (opsional)
 */
class EmployeeImportService
{
    public const HEADER = ['nama', 'nik', 'departemen', 'jabatan', 'tanggal_bergabung', 'email_atasan'];

    public const MAX_ROWS = 500;

    /** @return int jumlah karyawan yang ditambahkan */
    public function import(string $path): int
    {
        $rows = $this->read($path);
        if (! $rows) {
            throw ValidationException::withMessages(['file' => 'Berkas tidak berisi data karyawan.']);
        }
        if (count($rows) > self::MAX_ROWS) {
            throw ValidationException::withMessages(['file' => 'Maksimal ' . self::MAX_ROWS . ' baris per impor.']);
        }

        $departments = Department::pluck('id', 'department_name')->mapWithKeys(fn ($id, $n) => [mb_strtolower($n) => $id]);
        $positions = Position::pluck('id', 'position_name')->mapWithKeys(fn ($id, $n) => [mb_strtolower($n) => $id]);
        $managers = User::where('is_active', true)->whereHas('role', fn ($r) => $r->where('role_name', 'manager'))
            ->pluck('id', 'email')->mapWithKeys(fn ($id, $e) => [mb_strtolower($e) => $id]);
        $existingNik = Employee::pluck('nik')->flip();

        $errors = [];
        $data = [];
        $seen = [];
        foreach ($rows as $line => $r) {
            $problems = [];
            $name = trim($r['nama'] ?? '');
            $nik = trim($r['nik'] ?? '');
            $email = mb_strtolower(trim($r['email_atasan'] ?? ''));
            $date = $this->date(trim($r['tanggal_bergabung'] ?? ''));

            if ($name === '' || mb_strlen($name) > 120) {
                $problems[] = 'nama wajib diisi (maks. 120 huruf)';
            }
            if (! preg_match('/^\d{10,16}$/', $nik)) {
                $problems[] = 'NIK harus 10–16 digit angka';
            } elseif (isset($existingNik[$nik]) || isset($seen[$nik])) {
                $problems[] = "NIK {$nik} sudah terdaftar";
            }
            $seen[$nik] = true;

            $departmentId = $departments[mb_strtolower(trim($r['departemen'] ?? ''))] ?? null;
            $positionId = $positions[mb_strtolower(trim($r['jabatan'] ?? ''))] ?? null;
            if (! $departmentId) {
                $problems[] = 'departemen tidak dikenal';
            }
            if (! $positionId) {
                $problems[] = 'jabatan tidak dikenal';
            }
            if (! $date || $date->isFuture() || $date->year < 1950) {
                $problems[] = 'tanggal bergabung tidak valid';
            }
            if ($email !== '' && ! isset($managers[$email])) {
                $problems[] = 'email atasan bukan akun atasan yang aktif';
            }

            if ($problems) {
                $errors[] = "Baris {$line}: " . implode(', ', $problems) . '.';
            } else {
                $data[] = [
                    'name' => $name, 'nik' => $nik, 'department_id' => $departmentId, 'position_id' => $positionId,
                    'join_date' => $date->toDateString(), 'supervisor_id' => $email !== '' ? $managers[$email] : null,
                ];
            }
        }

        if ($errors) {
            $more = count($errors) > 5 ? ' (+' . (count($errors) - 5) . ' baris lain bermasalah)' : '';
            throw ValidationException::withMessages(['file' => 'Impor dibatalkan, tidak ada data yang disimpan. ' . implode(' ', array_slice($errors, 0, 5)) . $more]);
        }

        DB::transaction(fn () => collect($data)->each(fn ($row) => Employee::create($row)));

        return count($data);
    }

    /** @return array<int, array<string, string>> nomor baris di berkas => kolom */
    private function read(string $path): array
    {
        $handle = fopen($path, 'r');
        $first = (string) fgets($handle);
        $delimiter = substr_count($first, ';') >= substr_count($first, ',') ? ';' : ',';
        rewind($handle);

        $header = null;
        $rows = [];
        $line = 0;
        while (($cells = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            $line++;
            if ($cells === [null] || trim(implode('', $cells)) === '') {
                continue;
            }
            if ($header === null) {
                $header = array_map(fn ($h) => mb_strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $cells);
                if (array_diff(array_slice(self::HEADER, 0, 5), $header)) {
                    fclose($handle);
                    throw ValidationException::withMessages(['file' => 'Judul kolom tidak sesuai templat. Kolom wajib: ' . implode('; ', array_slice(self::HEADER, 0, 5)) . '.']);
                }
                continue;
            }
            $rows[$line] = array_combine($header, array_pad(array_slice($cells, 0, count($header)), count($header), ''));
        }
        fclose($handle);

        return $rows;
    }

    private function date(string $value): ?Carbon
    {
        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y'] as $format) {
            try {
                $date = Carbon::createFromFormat('!' . $format, $value);
                if ($date && $date->format($format) === $value) {
                    return $date;
                }
            } catch (\Throwable) {
                // coba format berikutnya
            }
        }

        return null;
    }
}
