<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    private array $defaults = [
        ['key' => 'app_name',        'value' => null,    'group' => 'identity'],
        ['key' => 'logo_light',      'value' => null,    'group' => 'identity'],
        ['key' => 'logo_dark',       'value' => null,    'group' => 'identity'],
        ['key' => 'favicon',         'value' => null,    'group' => 'identity'],
        ['key' => 'session_timeout',          'value' => '120',   'group' => 'security'],
        ['key' => 'password_expiration_days', 'value' => '0',     'group' => 'security'],
        ['key' => 'allow_self_deletion',      'value' => '0',     'group' => 'security'],
        ['key' => 'mail_from_name',    'value' => null, 'group' => 'email'],
        ['key' => 'mail_from_address', 'value' => null, 'group' => 'email'],
        ['key' => 'company_name',         'value' => null,  'group' => 'lgpd'],
        ['key' => 'dpo_email',            'value' => null,  'group' => 'lgpd'],
        ['key' => 'audit_retention_days', 'value' => '180', 'group' => 'lgpd'],
    ];

    public function run(): void
    {
        foreach ($this->defaults as $setting) {
            SystemSetting::firstOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'group' => $setting['group']]
            );
        }

        $this->command->info('System settings defaults seeded.');
    }
}
