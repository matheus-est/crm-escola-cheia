<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope for EventType that returns both system records (school_id IS NULL, is_system = true)
 * and tenant-specific records (school_id = active tenant) in the same query.
 *
 * This differs from TenantScope which would exclude NULL school_id records.
 */
class EventTypeScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $schoolId = app()->bound('tenant.school_id')
            ? app('tenant.school_id')
            : null;

        if ($schoolId !== null) {
            $builder->where(function (Builder $query) use ($schoolId, $model): void {
                $query->where($model->getTable().'.school_id', $schoolId)
                    ->orWhere($model->getTable().'.is_system', true);
            });
        }
    }
}
