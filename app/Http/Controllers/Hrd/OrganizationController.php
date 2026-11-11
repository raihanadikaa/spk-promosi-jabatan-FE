<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Master struktur organisasi: departemen dan jabatan (dengan level untuk usulan promosi).
 * Data yang masih dipakai karyawan tidak boleh dihapus (FK restrictOnDelete).
 */
class OrganizationController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'jabatan' ? 'jabatan' : 'departemen';

        return view('hrd.organization', [
            'tab' => $tab,
            'departments' => Department::withCount('employees')->orderBy('department_name')->get(),
            'positions' => Position::withCount('employees')->orderBy('level')->orderBy('position_name')->get(),
        ]);
    }

    /* ---------------- Departemen ---------------- */

    public function storeDepartment(Request $request): RedirectResponse
    {
        $data = $this->validateDepartment($request);
        Department::create($data);

        return to_route('hrd.organization')->with('success', "Departemen {$data['department_name']} ditambahkan.");
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $department->update($this->validateDepartment($request, $department));

        return to_route('hrd.organization')->with('success', 'Departemen diperbarui.');
    }

    public function destroyDepartment(Department $department): RedirectResponse
    {
        if ($department->employees()->exists()) {
            return to_route('hrd.organization')->withErrors(['organization' => "Departemen {$department->department_name} masih dipakai karyawan, jadi tidak bisa dihapus."]);
        }
        $department->delete();

        return to_route('hrd.organization')->with('success', "Departemen {$department->department_name} dihapus.");
    }

    /* ---------------- Jabatan ---------------- */

    public function storePosition(Request $request): RedirectResponse
    {
        $data = $this->validatePosition($request);
        Position::create($data);

        return to_route('hrd.organization', ['tab' => 'jabatan'])->with('success', "Jabatan {$data['position_name']} ditambahkan.");
    }

    public function updatePosition(Request $request, Position $position): RedirectResponse
    {
        $position->update($this->validatePosition($request, $position));

        return to_route('hrd.organization', ['tab' => 'jabatan'])->with('success', 'Jabatan diperbarui.');
    }

    public function destroyPosition(Position $position): RedirectResponse
    {
        if ($position->employees()->exists()) {
            return to_route('hrd.organization', ['tab' => 'jabatan'])->withErrors(['organization' => "Jabatan {$position->position_name} masih dipakai karyawan, jadi tidak bisa dihapus."]);
        }
        $position->delete();

        return to_route('hrd.organization', ['tab' => 'jabatan'])->with('success', "Jabatan {$position->position_name} dihapus.");
    }

    private function validateDepartment(Request $request, ?Department $department = null): array
    {
        return $request->validate([
            'department_name' => ['required', 'string', 'max:100', Rule::unique('departments', 'department_name')->ignore($department)],
        ], [
            'department_name.unique' => 'Nama departemen ini sudah ada.',
        ], ['department_name' => 'nama departemen']);
    }

    private function validatePosition(Request $request, ?Position $position = null): array
    {
        return $request->validate([
            'position_name' => ['required', 'string', 'max:100', Rule::unique('positions', 'position_name')->ignore($position)],
            'level' => ['required', 'integer', 'between:1,20'],
        ], [
            'position_name.unique' => 'Nama jabatan ini sudah ada.',
        ], ['position_name' => 'nama jabatan', 'level' => 'level']);
    }
}
