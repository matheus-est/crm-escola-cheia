<?php

declare(strict_types=1);

use App\Enums\SchoolYearStatus;
use App\Http\Requests\SchoolYear\SchoolYearStoreRequest;
use App\Models\School;
use App\Models\SchoolYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

it('gera uuid automaticamente via observer ao criar school year', function () {
    $school = School::create([
        'cnpj' => '12345678000195',
        'legal_name' => 'Escola Teste',
        'slug' => 'escola-teste',
    ]);

    $schoolYear = SchoolYear::withoutTenantScope()->create([
        'school_id' => $school->id,
        'name' => '2025',
        'start' => '2025-01-01',
        'end' => '2025-12-31',
        'status' => SchoolYearStatus::Planejamento,
    ]);

    expect($schoolYear->uuid)->not->toBeNull();
    expect($schoolYear->uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/');
});

it('falha na validacao quando fim e anterior a inicio', function () {
    $validator = Validator::make(
        [
            'name' => 'Ano Letivo 2025',
            'start' => '2025-12-31',
            'end' => '2025-01-01',
            'status' => 'planejamento',
        ],
        (new SchoolYearStoreRequest)->rules()
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('end'))->toBeTrue();
});

it('passa na validacao quando fim e igual a inicio', function () {
    $validator = Validator::make(
        [
            'name' => 'Ano Letivo 2025',
            'start' => '2025-06-01',
            'end' => '2025-06-01',
            'status' => 'ativo',
        ],
        (new SchoolYearStoreRequest)->rules()
    );

    expect($validator->passes())->toBeTrue();
});

it('falha na validacao com status invalido', function () {
    $validator = Validator::make(
        [
            'name' => 'Ano Letivo 2025',
            'start' => '2025-01-01',
            'end' => '2025-12-31',
            'status' => 'invalido',
        ],
        (new SchoolYearStoreRequest)->rules()
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('status'))->toBeTrue();
});
