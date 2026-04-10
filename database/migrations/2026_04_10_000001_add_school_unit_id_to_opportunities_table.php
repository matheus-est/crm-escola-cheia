<?php

declare(strict_types=1);

use App\Models\SchoolUnit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table): void {
            $table->foreignId('school_unit_id')
                ->nullable()
                ->after('school_id')
                ->constrained('school_units')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table): void {
            $table->dropForeignIdFor(SchoolUnit::class);
        });
    }
};
