<?php

declare(strict_types=1);

namespace App\Services\Acl;

use App\Models\TermVersion;
use App\Models\User;
use App\Models\UserTermAcceptance;
use App\Services\SettingService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TermService
{
    public function __construct(
        protected readonly SettingService $settingService,
    ) {}

    public function getActiveTerm(): ?TermVersion
    {
        $term = TermVersion::current()->first();

        if ($term) {
            $term->content = $this->resolvePlaceholders($term->content);
        }

        return $term;
    }

    private function resolvePlaceholders(string $content): string
    {
        return str_replace(
            ['{{company_name}}', '{{dpo_email}}'],
            [
                $this->settingService->get('company_name', '[Razão Social não configurada]'),
                $this->settingService->get('dpo_email', '[E-mail do DPO não configurado]'),
            ],
            $content
        );
    }

    public function getNextVersion(): string
    {
        $latest = TermVersion::query()
            ->orderByDesc('created_at')
            ->value('version');

        if (! $latest) {
            return '1.0';
        }

        $parts = explode('.', $latest);
        $major = (int) ($parts[0] ?? 1);
        $minor = (int) ($parts[1] ?? 0);

        return "{$major}.".($minor + 1);
    }

    public function filter(array $filters): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 10;
        $perPage = $perPage === 'all' || (int) $perPage === 0
            ? PHP_INT_MAX
            : (int) $perPage;

        $sortDir = ($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $sortBy = in_array($filters['sort_by'] ?? 'created_at', ['title', 'version', 'effective_at', 'created_at'])
            ? $filters['sort_by']
            : 'created_at';

        $paginator = TermVersion::query()
            ->withCount('acceptances')
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);

        $hasMultipleActive = TermVersion::where('is_active', true)->count() > 1;

        $paginator->through(fn (TermVersion $term): array => array_merge($term->toArray(), [
            'is_deletable' => $this->isTermDeletableFromCounts($term, $hasMultipleActive),
        ]));

        return $paginator;
    }

    private function isTermDeletableFromCounts(TermVersion $term, bool $hasMultipleActive): bool
    {
        if ($term->is_active) {
            return $hasMultipleActive;
        }

        return $term->acceptances_count === 0;
    }

    public function isTermDeletable(TermVersion $term): bool
    {
        if ($term->is_active) {
            return TermVersion::where('is_active', true)->count() > 1;
        }

        return $term->acceptances()->count() === 0;
    }

    public function canAcceptTerm(string $termUuid): bool
    {
        return $this->getActiveTerm()?->uuid === $termUuid;
    }

    public function getTermByUuid(string $uuid): TermVersion
    {
        return TermVersion::where('uuid', $uuid)->firstOrFail();
    }

    public function getUserAcceptance(User $user): ?UserTermAcceptance
    {
        $activeTerm = $this->getActiveTerm();

        if (! $activeTerm) {
            return null;
        }

        return UserTermAcceptance::where('user_id', $user->id)
            ->where('term_version_id', $activeTerm->id)
            ->first();
    }

    public function hasAcceptedCurrentTerm(User $user): bool
    {
        return $this->getUserAcceptance($user) !== null;
    }

    public function needsAcceptance(User $user): bool
    {
        $activeTerm = $this->getActiveTerm();

        if (! $activeTerm) {
            return false;
        }

        return ! $this->hasAcceptedCurrentTerm($user);
    }

    public function acceptTerm(User $user, int $termVersionId, ?string $ipAddress = null, ?string $userAgent = null): UserTermAcceptance
    {
        return UserTermAcceptance::updateOrCreate(
            [
                'user_id' => $user->id,
                'term_version_id' => $termVersionId,
            ],
            [
                'accepted_at' => now(),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]
        );
    }

    public function createTermVersion(array $data, User $creator): TermVersion
    {
        return DB::transaction(function () use ($data, $creator): TermVersion {
            $effectiveAt = \Carbon\Carbon::parse($data['effective_at'])->startOfDay();
            $shouldActivate = $effectiveAt->lte(now()->startOfDay());

            if ($shouldActivate) {
                TermVersion::where('is_active', true)->update(['is_active' => false]);
            }

            return TermVersion::create([
                'version' => $data['version'],
                'title' => $data['title'] ?? null,
                'content' => $data['content'],
                'is_active' => $shouldActivate,
                'effective_at' => $effectiveAt,
                'created_by' => $creator->id,
            ]);
        });
    }

    public function updateTermVersion(TermVersion $term, array $data): TermVersion
    {
        return DB::transaction(function () use ($term, $data): TermVersion {
            $effectiveAt = \Carbon\Carbon::parse($data['effective_at'])->startOfDay();
            $shouldActivate = $effectiveAt->lte(now()->startOfDay());

            if ($shouldActivate && ! $term->is_active) {
                TermVersion::where('is_active', true)
                    ->where('id', '!=', $term->id)
                    ->update(['is_active' => false]);
            }

            $term->update([
                'version' => $data['version'],
                'title' => $data['title'] ?? null,
                'content' => $data['content'],
                'effective_at' => $effectiveAt,
                'is_active' => $shouldActivate,
            ]);

            return $term->fresh();
        });
    }

    public function activateScheduledTerms(): array
    {
        $pending = TermVersion::query()
            ->where('is_active', false)
            ->whereDate('effective_at', '<=', now())
            ->orderBy('effective_at')
            ->get();

        if ($pending->isEmpty()) {
            return [];
        }

        $toActivate = $pending->last();

        DB::transaction(function () use ($toActivate): void {
            TermVersion::where('is_active', true)->update(['is_active' => false]);
            $toActivate->update(['is_active' => true]);
        });

        logger()->info('TermVersion activated by scheduler', [
            'term_id' => $toActivate->id,
            'term_uuid' => $toActivate->uuid,
            'term_version' => $toActivate->version,
            'effective_at' => $toActivate->effective_at->toISOString(),
            'activated_at' => now()->toISOString(),
        ]);

        return $pending->all();
    }

    public function deleteTermVersion(TermVersion $term, ?User $deletedBy = null): void
    {
        DB::transaction(function () use ($term, $deletedBy): void {
            logger()->info('TermVersion deleted (LGPD audit trail)', [
                'deleted_term_id' => $term->id,
                'deleted_term_uuid' => $term->uuid,
                'deleted_term_version' => $term->version,
                'deleted_term_title' => $term->title,
                'deleted_term_is_active' => $term->is_active,
                'acceptances_count' => $term->acceptances()->count(),
                'deleted_by_user_id' => $deletedBy?->id,
                'deleted_by_user_email' => $deletedBy?->email,
                'deleted_at' => now()->toISOString(),
                'deleted_from_ip' => request()->ip(),
                'deleted_from_user_agent' => request()->userAgent(),
            ]);

            $term->delete();
        });
    }
}
