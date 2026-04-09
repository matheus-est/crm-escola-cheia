<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cpf = preg_replace('/\D/', '', (string) $value);

        if (strlen($cpf) !== 11) {
            $fail('CPF inválido.');

            return;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            $fail('CPF inválido.');

            return;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cpf[$i] * ($t + 1 - $i);
            }
            $remainder = (10 * $sum) % 11;
            $digit = $remainder === 10 ? 0 : $remainder;

            if ((int) $cpf[$t] !== $digit) {
                $fail('CPF inválido.');

                return;
            }
        }
    }
}
