<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardians', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            $table->string('name');
            $table->string('cpf');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('zip_code', 9)->nullable();
            $table->string('street')->nullable();
            $table->string('number')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('complement')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('city')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'cpf']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardians');
    }
};
