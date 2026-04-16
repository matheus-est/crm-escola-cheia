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
        $uuid = $this->input('assigned_user_uuid');
        if ($uuid === null || $uuid === '') {
            $this->merge(['assigned_user_uuid' => auth()->user()->uuid]);
            $uuid = auth()->user()->uuid;
        }

        if ($this->has('assigned_user_uuid') && $uuid !== null && $uuid !== '') {
            $user = User::query()->where('uuid', $uuid)->first();
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
            'due_at' => ['nullable', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'due_at.after' => 'O prazo deve ser uma data e hora no futuro.',
        ];
    }
}
