<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_segment', function (Blueprint $table) {
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->foreignId('segment_id')->constrained('segments')->cascadeOnDelete();
            $table->primary(['school_id', 'segment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_segment');
    }
};
