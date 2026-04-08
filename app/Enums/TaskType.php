<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskType: string
{
    case RetornoLigacao = 'retorno_ligacao';
    case Agendamento = 'agendamento';
    case LembreteAgenda = 'lembrete_agenda';
    case Reagendamento = 'reagendamento';
    case DoubleCheck = 'double_check';
    case ProvavelMatricula = 'provavel_matricula';
    case Evento = 'evento';
    case LembreteEvento = 'lembrete_evento';
    case ReagendamentoEvento = 'reagendamento_evento';
    case DoubleCheckEvento = 'double_check_evento';

    public function isSchedule(): bool
    {
        return match ($this) {
            self::Agendamento, self::LembreteAgenda, self::LembreteEvento => true,
            default => false,
        };
    }
}
