<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'observations' => $this->observations,
            'history' => $this->history,
            'indications' => $this->indications,
            'registration_type' => $this->registration_type,
            'renitente_count' => $this->renitente_count,
            'created_at' => $this->created_at,
            'student' => $this->whenLoaded('student', fn () => [
                'uuid' => $this->student?->uuid,
                'name' => $this->student?->name,
                'birth_date' => $this->student?->date_of_birth?->format('Y-m-d'),
            ]),
            'guardian' => $this->whenLoaded('guardian', fn () => $this->guardian ? [
                'uuid' => $this->guardian->uuid,
                'name' => $this->guardian->name,
            ] : null),
            'grade' => $this->whenLoaded('grade', fn () => $this->grade ? [
                'uuid' => $this->grade->uuid,
                'name' => $this->grade->name,
            ] : null),
            'segment' => $this->whenLoaded('segment', fn () => $this->segment ? [
                'uuid' => $this->segment->uuid,
                'name' => $this->segment->name,
            ] : null),
            'school_year' => $this->whenLoaded('schoolYear', fn () => $this->schoolYear ? [
                'uuid' => $this->schoolYear->uuid,
                'name' => $this->schoolYear->name,
            ] : null),
            'responsible_user' => $this->whenLoaded('responsibleUser', fn () => $this->responsibleUser ? [
                'uuid' => $this->responsibleUser->uuid,
                'name' => $this->responsibleUser->name,
            ] : null),
            'school_unit' => $this->whenLoaded('schoolUnit', fn () => $this->schoolUnit ? [
                'uuid' => $this->schoolUnit->uuid,
                'name' => $this->schoolUnit->name,
            ] : null),
        ];
    }
}
