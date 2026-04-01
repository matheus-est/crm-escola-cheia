<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait for domain models that belong to a specific tenant (school).
 *
 * Automatically applies TenantScope as a global scope, restricting all queries
 * to the currently active school. Use withoutTenantScope() for cross-tenant queries.
 */
trait BelongsToTenant
{
    /**
     * Boot the trait and register the global tenant scope.
     */
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Return a query builder instance without the tenant scope applied.
     * Use this for cross-tenant queries (e.g., admin operations).
     */
    public static function withoutTenantScope(): Builder
    {
        return static::withoutGlobalScope(TenantScope::class);
    }
}
