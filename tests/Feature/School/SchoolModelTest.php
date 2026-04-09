<?php

declare(strict_types=1);

use App\Models\School;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('gera slug automaticamente a partir da razao social', function () {
    $school = School::create([
        'cnpj' => '12345678000195',
        'legal_name' => 'Escola Nova Esperança',
    ]);

    expect($school->slug)->toBe('escola-nova-esperanca');
});

it('resolve colisao de slug com sufixo numerico', function () {
    School::create(['cnpj' => '11111111000191', 'legal_name' => 'Escola Alfa']);
    $school2 = School::create(['cnpj' => '22222222000100', 'legal_name' => 'Escola Alfa']);
    $school3 = School::create(['cnpj' => '33333333000109', 'legal_name' => 'Escola Alfa']);

    expect($school2->slug)->toBe('escola-alfa-2');
    expect($school3->slug)->toBe('escola-alfa-3');
});

it('rejeita cnpj duplicado', function () {
    School::create(['cnpj' => '12345678000195', 'legal_name' => 'Escola A']);

    expect(fn () => School::create(['cnpj' => '12345678000195', 'legal_name' => 'Escola B']))
        ->toThrow(QueryException::class);
});
