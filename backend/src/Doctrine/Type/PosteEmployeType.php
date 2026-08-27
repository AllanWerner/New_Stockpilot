<?php

declare(strict_types=1);

namespace App\Doctrine\Type;

use App\Entity\Enum\PosteEmploye;

final class PosteEmployeType extends AbstractPgEnumType
{
    protected function enumClass(): string
    {
        return PosteEmploye::class;
    }

    protected function typeName(): string
    {
        return 'poste_employe';
    }
}
