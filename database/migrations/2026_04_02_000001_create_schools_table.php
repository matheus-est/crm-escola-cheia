<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('cnpj', 14)->unique();
            $table->string('razao_social');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->json('address_json')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('observations')->nullable();
            $table->unsignedTinyInteger('unassigned_lead_alert_days')->default(3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
