<?php

namespace App\Http\Requests;

use App\Models\Criterion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateWeightsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('hrd') ?? false;
    }

    public function rules(): array
    {
        $rules = ['criteria' => ['required', 'array']];

        foreach (Criterion::pluck('code') as $code) {
            $rules["criteria.$code.weight"] = ['required', 'integer', 'min:0', 'max:100'];
            $rules["criteria.$code.type"] = ['required', 'in:benefit,cost'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'criteria.*.weight.required' => 'Setiap kriteria wajib diberi bobot.',
            'criteria.*.weight.integer' => 'Bobot harus bilangan bulat (persen).',
            'criteria.*.weight.min' => 'Bobot tidak boleh negatif.',
            'criteria.*.weight.max' => 'Bobot maksimal 100%.',
            'criteria.*.type.in' => 'Jenis kriteria harus benefit atau cost.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }
                $total = collect($this->input('criteria'))->sum(fn ($c) => (int) $c['weight']);
                if ($total !== 100) {
                    $validator->errors()->add('criteria', "Total bobot harus tepat 100% (sekarang {$total}%).");
                }
            },
        ];
    }
}
