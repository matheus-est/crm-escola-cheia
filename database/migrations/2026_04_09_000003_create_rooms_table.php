<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('school_id')->constrained('schools');
            $table->string('name', 255);
            $table->unsignedInteger('capacity')->nullable();
            $table->boolean('is_external')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
