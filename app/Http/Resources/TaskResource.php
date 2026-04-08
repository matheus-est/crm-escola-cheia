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
            'is_schedule' => $this->type?->isSchedule() ?? false,
            'status' => $this->status,
            'notes' => $this->notes,
            'scheduled_at' => $this->scheduled_at,
            'due_at' => $this->due_at,
            'completed_at' => $this->completed_at,
            'cancelled_at' => $this->cancelled_at,
            'opportunity' => $this->whenLoaded('opportunity', fn () => [
                'uuid' => $this->opportunity->uuid,
                'status' => $this->opportunity->status,
                'student' => $this->opportunity->student ? [
                    'name' => $this->opportunity->student->nome,
                ] : null,
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
