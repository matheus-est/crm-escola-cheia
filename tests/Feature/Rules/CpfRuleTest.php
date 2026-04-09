<?php

declare(strict_types=1);

use App\Rules\CpfRule;
use Illuminate\Support\Facades\Validator;

function validateCpf(string $cpf): bool
{
    $validator = Validator::make(
        ['cpf' => $cpf],
        ['cpf' => [new CpfRule]],
    );

    return ! $validator->fails();
}

it('passes with a valid CPF', function (): void {
    expect(validateCpf('529.982.247-25'))->toBeTrue();
});

it('passes with a valid CPF without formatting', function (): void {
    expect(validateCpf('52998224725'))->toBeTrue();
});

it('fails when CPF has fewer than 11 digits', function (): void {
    expect(validateCpf('123.456.789-0'))->toBeFalse();
});

it('fails when CPF has more than 11 digits', function (): void {
    expect(validateCpf('123.456.789-001'))->toBeFalse();
});

it('fails when all digits are equal', function (): void {
    foreach (range(0, 9) as $digit) {
        $cpf = str_repeat((string) $digit, 11);
        expect(validateCpf($cpf))->toBeFalse();
    }
});

it('fails with a known invalid CPF', function (): void {
    expect(validateCpf('111.222.333-44'))->toBeFalse();
});

it('fails when first verifier digit is wrong', function (): void {
    // 529.982.247-25 is valid; change first verifier to get invalid
    expect(validateCpf('529.982.247-35'))->toBeFalse();
});

it('fails when second verifier digit is wrong', function (): void {
    expect(validateCpf('529.982.247-26'))->toBeFalse();
});
