<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type,
            'task_type' => $this->type ? ['name' => $this->type->label()] : null,
            'is_schedule' => $this->type?->isSchedule() ?? false,
            'status' => $this->status,
            'notes' => $this->notes,
            'refusal_category' => $this->refusal_category,
            'refusal_detail' => $this->refusal_detail,
            'created_at' => $this->created_at,
            'scheduled_at' => $this->scheduled_at,
            'due_at' => $this->due_at,
            'completed_at' => $this->completed_at,
            'cancelled_at' => $this->cancelled_at,
            'opportunity' => $this->whenLoaded('opportunity', fn () => [
                'uuid' => $this->opportunity->uuid,
                'status' => $this->opportunity->status,
                'student' => $this->opportunity->student ? [
                    'name' => $this->opportunity->student->name,
                ] : null,
                'guardian' => $this->opportunity->guardian ? [
                    'name' => $this->opportunity->guardian->name,
                    'phone' => $this->opportunity->guardian->phone,
                    'email' => $this->opportunity->guardian->email,
                ] : null,
                'responsible_user' => $this->opportunity->responsibleUser ? [
                    'uuid' => $this->opportunity->responsibleUser->uuid,
                    'name' => $this->opportunity->responsibleUser->name,
                ] : null,
                'segment' => $this->opportunity->segment ? ['name' => $this->opportunity->segment->name] : null,
                'grade' => $this->opportunity->grade ? ['name' => $this->opportunity->grade->name] : null,
                'school_year' => $this->opportunity->schoolYear ? ['name' => $this->opportunity->schoolYear->name] : null,
                'school_unit' => $this->opportunity->schoolUnit ? ['name' => $this->opportunity->schoolUnit->name] : null,
            ]),
            'assigned_user' => $this->whenLoaded('assignedUser', fn () => $this->assignedUser ? [
                'uuid' => $this->assignedUser->uuid,
                'name' => $this->assignedUser->name,
            ] : null),
            'outcome' => $this->whenLoaded('outcome', fn () => $this->outcome ? [
                'uuid' => $this->outcome->uuid,
                'name' => $this->outcome->name,
                'is_refusal' => $this->outcome->is_refusal,
            ] : null),
        ];
    }
}
