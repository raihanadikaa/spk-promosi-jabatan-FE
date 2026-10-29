<?php

namespace App\Http\Requests;

use App\Models\Criterion;
use Illuminate\Foundation\Http\FormRequest;

class SaveAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('manager') ?? false;
    }

    public function rules(): array
    {
        $submit = $this->input('action') === 'submit';
        $rules = [
            'action' => ['required', 'in:draft,submit'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'scores' => ['array'],
        ];

        // Draft boleh belum lengkap; kirim wajib semua kriteria terisi dalam rentangnya.
        foreach (Criterion::where('input_type', '!=', 'auto')->get() as $c) {
            $rules["scores.{$c->code}"] = [$submit ? 'required' : 'nullable', 'integer', "min:{$c->min_value}", "max:{$c->max_value}"];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return Criterion::pluck('criteria_name', 'code')
            ->mapWithKeys(fn ($name, $code) => ["scores.$code" => "nilai $name"])
            ->all();
    }

    public function messages(): array
    {
        return [
            'scores.*.required' => ':Attribute wajib diisi sebelum dikirim.',
            'scores.*.integer' => ':Attribute harus berupa bilangan bulat.',
            'scores.*.min' => ':Attribute minimal :min.',
            'scores.*.max' => ':Attribute maksimal :max.',
        ];
    }

    /** @return array<string, int|null> */
    public function scores(): array
    {
        return collect($this->validated('scores', []))
            ->map(fn ($v) => $v === null || $v === '' ? null : (int) $v)
            ->all();
    }
}
