<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum RoleEmploye: string
{
    case GERANT = 'GERANT';
    case EMPLOYE = 'EMPLOYE';

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }
}
