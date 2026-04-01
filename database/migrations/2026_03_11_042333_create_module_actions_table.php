<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('module_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('label');
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->unique(['module_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_actions');
    }
};
