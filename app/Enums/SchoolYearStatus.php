<?php

declare(strict_types=1);

namespace App\Enums;

enum SchoolYearStatus: string
{
    case Ativo = 'ativo';
    case Encerrado = 'encerrado';
    case Planejamento = 'planejamento';
}
