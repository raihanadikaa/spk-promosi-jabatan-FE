<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('hrd') ?? false;
    }

    public function rules(): array
    {
        return [
            'period_name' => ['required', 'string', 'max:60', 'unique:assessment_periods,period_name'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'assessment_deadline' => ['required', 'date', 'after_or_equal:start_date', 'before_or_equal:end_date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'period_name' => 'nama periode',
            'start_date' => 'tanggal mulai',
            'end_date' => 'tanggal selesai',
            'assessment_deadline' => 'batas penilaian',
        ];
    }

    public function messages(): array
    {
        return [
            'period_name.unique' => 'Nama periode sudah dipakai.',
            'end_date.after' => 'Tanggal selesai harus setelah tanggal mulai.',
            'assessment_deadline.after_or_equal' => 'Batas penilaian harus di dalam rentang periode.',
            'assessment_deadline.before_or_equal' => 'Batas penilaian harus di dalam rentang periode.',
        ];
    }
}
