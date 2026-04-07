<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guardians', function (Blueprint $table): void {
            $table->string('cep', 8)->nullable()->after('email');
            $table->string('logradouro')->nullable()->after('cep');
            $table->string('numero')->nullable()->after('logradouro');
            $table->string('estado', 2)->nullable()->after('numero');
            $table->string('cidade')->nullable()->after('estado');
            $table->string('bairro')->nullable()->after('cidade');
        });
    }

    public function down(): void
    {
        Schema::table('guardians', function (Blueprint $table): void {
            $table->dropColumn(['bairro', 'cidade', 'estado', 'numero', 'logradouro', 'cep']);
        });
    }
};
