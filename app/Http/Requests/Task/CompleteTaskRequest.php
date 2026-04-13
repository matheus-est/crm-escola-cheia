<?php

declare(strict_types=1);

namespace App\Http\Requests\Task;

use App\Models\Outcome;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $outcome = Outcome::query()->where('uuid', $this->input('outcome_uuid'))->first();

        $this->merge([
            'is_refusal' => $outcome?->is_refusal ?? false,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'outcome_uuid' => ['required', 'string', 'exists:outcomes,uuid'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'refusal_category' => [
                'nullable',
                'required_if:is_refusal,true',
                Rule::in([
                    'fatores_externos',
                    'fatores_internos',
                    'pedagogicos',
                    'administrativos',
                    'sem_interesse',
                    'questoes_financeiras',
                    'optou_por_concorrente',
                    'sem_retorno',
                ]),
            ],
            'refusal_detail' => ['nullable', 'required_if:is_refusal,true', 'string', 'max:2000'],
        ];
    }
}
