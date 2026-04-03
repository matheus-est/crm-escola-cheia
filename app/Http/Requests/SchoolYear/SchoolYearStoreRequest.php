<?php

declare(strict_types=1);

namespace App\Http\Requests\SchoolYear;

use App\Enums\SchoolYearStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class SchoolYearStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'inicio' => ['required', 'date'],
            'fim' => ['required', 'date', 'after_or_equal:inicio'],
            'status' => ['required', new Enum(SchoolYearStatus::class)],
        ];
    }
}
