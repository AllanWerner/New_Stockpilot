<?php

declare(strict_types=1);

namespace App\Doctrine\Type;

use App\Entity\Enum\RoleEmploye;

final class RoleEmployeType extends AbstractPgEnumType
{
    protected function enumClass(): string
    {
        return RoleEmploye::class;
    }

    protected function typeName(): string
    {
        return 'role_employe';
    }
}
