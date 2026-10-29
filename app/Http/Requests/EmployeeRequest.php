<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Tambah / ubah data karyawan (HRD).
 */
class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('hrd') ?? false;
    }

    public function rules(): array
    {
        $employee = $this->route('employee');
        $managerRole = Role::where('role_name', 'manager')->value('id');
        $karyawanRole = Role::where('role_name', 'karyawan')->value('id');

        return [
            'name' => ['required', 'string', 'max:120'],
            'nik' => ['required', 'digits_between:10,16', Rule::unique('employees', 'nik')->ignore($employee)],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'supervisor_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role_id', $managerRole)->where('is_active', true)],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role_id', $karyawanRole), Rule::unique('employees', 'user_id')->ignore($employee)],
            'join_date' => ['required', 'date', 'before_or_equal:today', 'after:1950-01-01'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'nik' => 'NIK',
            'department_id' => 'departemen',
            'position_id' => 'jabatan',
            'supervisor_id' => 'atasan penilai',
            'user_id' => 'akun portal',
            'join_date' => 'tanggal bergabung',
        ];
    }

    public function messages(): array
    {
        return [
            'nik.unique' => 'NIK ini sudah terdaftar.',
            'nik.digits_between' => 'NIK harus 10–16 digit angka.',
            'supervisor_id.exists' => 'Atasan penilai harus akun atasan yang aktif.',
            'user_id.unique' => 'Akun ini sudah tertaut ke karyawan lain.',
            'join_date.before_or_equal' => 'Tanggal bergabung tidak boleh di masa depan.',
        ];
    }
}
