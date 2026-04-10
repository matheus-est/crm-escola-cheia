<?php

declare(strict_types=1);

namespace App\Http\Requests\Opportunity;

use App\Enums\OpportunityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class UpdateOpportunityStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(array_column(OpportunityStatus::cases(), 'value'))],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function () use ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $opportunity = $this->route('opportunity');

            if ($opportunity !== null && $opportunity->status->isTerminal()) {
                throw ValidationException::withMessages([
                    'status' => 'Oportunidades com status terminal não podem ser alteradas.',
                ]);
            }
        });
    }
}
