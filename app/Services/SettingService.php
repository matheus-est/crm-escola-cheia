<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SettingService
{
    private const CACHE_TTL = 3600;

    private const STORAGE_DISK = 'public';

    private const STORAGE_PATH = 'settings';

    private const GROUPS = ['identity', 'security', 'email', 'lgpd'];

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            "setting.{$key}",
            self::CACHE_TTL,
            fn () => SystemSetting::where('key', $key)->value('value') ?? $default
        );
    }

    public function set(string $key, mixed $value): void
    {
        $group = $this->resolveGroup($key);

        SystemSetting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );

        Cache::forget("setting.{$key}");
        Cache::forget("setting.group.{$group}");
    }

    public function getGroup(string $group): array
    {
        return Cache::remember(
            "setting.group.{$group}",
            self::CACHE_TTL,
            fn () => SystemSetting::where('group', $group)
                ->get()
                ->pluck('value', 'key')
                ->toArray()
        );
    }

    public function setGroup(string $group, array $data): void
    {
        foreach ($data as $key => $value) {
            SystemSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group]
            );

            Cache::forget("setting.{$key}");
        }

        Cache::forget("setting.group.{$group}");
    }

    public function flushAll(): void
    {
        foreach (self::GROUPS as $group) {
            Cache::forget("setting.group.{$group}");
        }

        SystemSetting::pluck('key')->each(
            fn (string $key) => Cache::forget("setting.{$key}")
        );
    }

    public function uploadImage(string $key, ?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        // NOTE: keep in sync with IdentitySettingsRequest (mimes:jpeg,png,webp,svg)
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];

        if (! in_array($file->getMimeType(), $allowedMimes, true)) {
            throw new InvalidArgumentException('Invalid image type. Allowed: JPEG, PNG, WebP, SVG.');
        }

        if ($file->getSize() > 2 * 1024 * 1024) {
            throw new InvalidArgumentException('Image size must not exceed 2MB.');
        }

        $existing = $this->get($key);
        if ($existing && Str::startsWith($existing, self::STORAGE_PATH)) {
            Storage::disk(self::STORAGE_DISK)->delete($existing);
        }

        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs(self::STORAGE_PATH, $filename, self::STORAGE_DISK);

        $this->set($key, $path);

        return $path;
    }

    private function resolveGroup(string $key): string
    {
        return match (true) {
            in_array($key, ['app_name', 'logo_light', 'logo_dark', 'favicon'], true) => 'identity',
            in_array($key, ['session_timeout', 'password_expiration_days', 'allow_self_deletion'], true) => 'security',
            in_array($key, ['mail_from_name', 'mail_from_address'], true) => 'email',
            in_array($key, ['company_name', 'dpo_email', 'audit_retention_days'], true) => 'lgpd',
            default => 'general',
        };
    }
}
