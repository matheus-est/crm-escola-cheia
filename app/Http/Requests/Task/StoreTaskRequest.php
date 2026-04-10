<?php

declare(strict_types=1);

namespace App\Http\Requests\Task;

use App\Enums\TaskType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('assigned_user_uuid') && $this->input('assigned_user_uuid') !== null && $this->input('assigned_user_uuid') !== '') {
            $user = User::query()->where('uuid', $this->input('assigned_user_uuid'))->first();
            $this->merge(['assigned_user_id' => $user?->id]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'opportunity_uuid' => ['required', 'string', 'exists:opportunities,uuid'],
            'type' => ['required', Rule::enum(TaskType::class)],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'scheduled_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
