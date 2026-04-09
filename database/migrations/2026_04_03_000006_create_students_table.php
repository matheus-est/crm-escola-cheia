<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table): void {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('cpf')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'cpf']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
