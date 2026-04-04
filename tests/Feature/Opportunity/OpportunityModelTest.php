<?php

declare(strict_types=1);

use App\Models\Opportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

it('não é possível criar oportunidade sem tenant ativo', function (): void {
    app()->forgetInstance('tenant.school_id');

    $threw = false;

    try {
        Opportunity::create([
            'status' => 'cadastro_inicial',
        ]);
    } catch (Throwable) {
        $threw = true;
    }

    expect($threw)->toBeTrue();
});
