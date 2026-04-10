<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools');
            $table->string('title', 255);
            $table->dateTime('event_date')->nullable();
            $table->boolean('has_no_date')->default(false);
            $table->foreignId('grade_id')->nullable()->constrained('grades');
            $table->string('location', 255)->nullable();
            $table->unsignedInteger('max_capacity')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['school_id', 'event_date', 'grade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
