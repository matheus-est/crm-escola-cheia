<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\LeadSource;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LeadSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            'Indicação',
            'Site',
            'Instagram',
            'Facebook',
            'WhatsApp',
            'Evento',
            'Outdoor',
            'Busca orgânica',
        ];

        foreach ($sources as $sourceName) {
            LeadSource::withoutGlobalScopes()->firstOrCreate(
                ['name' => $sourceName],
                [
                    'uuid' => (string) Str::uuid(),
                    'is_system' => true,
                    'is_active' => true,
                    'school_id' => null,
                ]
            );
        }

        $this->command->info('LeadSource: '.count($sources).' system sources ensured.');
    }
}
