<?php

declare(strict_types=1);

namespace App\Enums;

enum OpportunityStatus: string
{
    case CadastroInicial = 'cadastro_inicial';
    case Agendamento = 'agendamento';
    case Visita = 'visita';
    case Matricula = 'matricula';
    case Recusado = 'recusado';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Matricula, self::Recusado => true,
            default => false,
        };
    }
}
