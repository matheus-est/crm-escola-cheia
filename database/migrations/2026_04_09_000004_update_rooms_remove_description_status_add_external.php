<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropColumn(['description', 'is_active']);
            $table->boolean('is_external')->default(false)->after('capacity');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropColumn('is_external');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }
};
