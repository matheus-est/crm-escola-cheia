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

    public function label(): string
    {
        return match ($this) {
            self::RetornoLigacao => 'Retorno de Ligação',
            self::Agendamento => 'Agendamento',
            self::LembreteAgenda => 'Lembrete de Agenda',
            self::Reagendamento => 'Reagendamento',
            self::DoubleCheck => 'Double Check',
            self::ProvavelMatricula => 'Provável Matrícula',
            self::Evento => 'Evento',
            self::LembreteEvento => 'Lembrete de Evento',
            self::ReagendamentoEvento => 'Reagendamento de Evento',
            self::DoubleCheckEvento => 'Double Check Evento',
        };
    }

    /**
     * Returns the allowed TaskType cases for a given registration_type.
     *
     * @return self[]
     */
    public static function forRegistrationType(?string $registrationType): array
    {
        return match ($registrationType) {
            'agendamento' => [
                self::RetornoLigacao,
                self::Agendamento,
                self::LembreteAgenda,
                self::Reagendamento,
                self::DoubleCheck,
                self::ProvavelMatricula,
            ],
            'evento' => [
                self::Evento,
                self::LembreteEvento,
                self::ReagendamentoEvento,
                self::DoubleCheckEvento,
            ],
            default => self::cases(),
        };
    }
}
