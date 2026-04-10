<?php

declare(strict_types=1);

use App\Models\School;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('armazena slug informado sem modificacao', function () {
    $school = School::create([
        'cnpj' => '12345678000195',
        'legal_name' => 'Escola Nova Esperança',
        'slug' => 'escola-nova-esperanca',
    ]);

    expect($school->slug)->toBe('escola-nova-esperanca');
});

it('rejeita cnpj duplicado', function () {
    School::create(['cnpj' => '12345678000195', 'legal_name' => 'Escola A', 'slug' => 'escola-a']);

    expect(fn () => School::create(['cnpj' => '12345678000195', 'legal_name' => 'Escola B', 'slug' => 'escola-b']))
        ->toThrow(QueryException::class);
});
