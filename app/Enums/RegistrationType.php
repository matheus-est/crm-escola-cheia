<?php

declare(strict_types=1);

namespace App\Enums;

enum RegistrationType: string
{
    case Agendamento = 'agendamento';
    case Evento = 'evento';
}
