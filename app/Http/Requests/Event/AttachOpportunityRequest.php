<?php

declare(strict_types=1);

namespace App\Http\Requests\Event;

use App\Models\Opportunity;
use Illuminate\Foundation\Http\FormRequest;

class AttachOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'opportunity_uuid' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $exists = Opportunity::where('uuid', $value)->exists();
                    if (! $exists) {
                        $fail('Oportunidade não encontrada.');
                    }
                },
            ],
        ];
    }
}
