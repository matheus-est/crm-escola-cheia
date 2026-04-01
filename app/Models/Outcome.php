<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outcome extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'task_type',
        'is_refusal',
        'opens_window',
    ];

    protected function casts(): array
    {
        return [
            'is_refusal' => 'boolean',
        ];
    }

    public function actions(): HasMany
    {
        return $this->hasMany(OutcomeAction::class);
    }
}
