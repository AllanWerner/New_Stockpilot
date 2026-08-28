<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum RoleEmploye: string
{
    case GERANT = 'GERANT';
    case EMPLOYE = 'EMPLOYE';
}
