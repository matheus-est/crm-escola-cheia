<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->renameColumn('name', 'title');
            $table->string('event_type', 60)->nullable()->after('title');
            $table->boolean('has_no_date')->default(false)->after('event_type');
            $table->foreignId('grade_id')->nullable()->constrained('grades')->after('has_no_date');
        });

        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn(['description', 'is_active']);
        });

        Schema::table('events', function (Blueprint $table): void {
            $table->dateTime('event_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table): void {
            $table->dateTime('event_date')->nullable(false)->change();
        });

        Schema::table('events', function (Blueprint $table): void {
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
        });

        Schema::table('events', function (Blueprint $table): void {
            $table->dropForeign(['grade_id']);
            $table->dropColumn(['grade_id', 'has_no_date', 'event_type']);
            $table->renameColumn('title', 'name');
        });
    }
};
