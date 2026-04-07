<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table): void {
            $table->text('history')->nullable()->after('observations');
            $table->text('indications')->nullable()->after('history');
            $table->string('registration_type')->nullable()->after('indications');
            $table->foreignId('segment_id')->nullable()->constrained('segments')->nullOnDelete()->after('registration_type');
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table): void {
            $table->dropForeign(['segment_id']);
            $table->dropColumn(['segment_id', 'registration_type', 'indications', 'history']);
        });
    }
};
