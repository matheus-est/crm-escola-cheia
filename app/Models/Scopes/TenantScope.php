<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope that restricts queries to the currently active tenant (school).
 *
 * The active school ID is resolved from the 'tenant.school_id' container binding.
 * If no binding is present (e.g., outside a tenant request context), no filter is applied.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $schoolId = app()->bound('tenant.school_id')
            ? app('tenant.school_id')
            : null;

        if ($schoolId !== null) {
            $builder->where($model->getTable().'.school_id', $schoolId);
        }
    }
}
