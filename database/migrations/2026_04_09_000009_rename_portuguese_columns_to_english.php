<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('lead_sources', 'nome')) {
            Schema::table('lead_sources', function (Blueprint $table): void {
                $table->renameColumn('nome', 'name');
            });
        }

        if (Schema::hasColumn('schools', 'razao_social')) {
            Schema::table('schools', function (Blueprint $table): void {
                $table->renameColumn('razao_social', 'legal_name');
            });
        }

        if (Schema::hasColumn('schools', 'nome_fantasia')) {
            Schema::table('schools', function (Blueprint $table): void {
                $table->renameColumn('nome_fantasia', 'trade_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lead_sources', 'name')) {
            Schema::table('lead_sources', function (Blueprint $table): void {
                $table->renameColumn('name', 'nome');
            });
        }

        if (Schema::hasColumn('schools', 'legal_name')) {
            Schema::table('schools', function (Blueprint $table): void {
                $table->renameColumn('legal_name', 'razao_social');
            });
        }

        if (Schema::hasColumn('schools', 'trade_name')) {
            Schema::table('schools', function (Blueprint $table): void {
                $table->renameColumn('trade_name', 'nome_fantasia');
            });
        }
    }
};
