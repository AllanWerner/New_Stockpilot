<?php

declare(strict_types=1);

namespace App\Doctrine\Type;

use App\Entity\Enum\TypeMouvement;

final class TypeMouvementType extends AbstractPgEnumType
{
    protected function enumClass(): string
    {
        return TypeMouvement::class;
    }

    protected function typeName(): string
    {
        return 'type_mouvement';
    }
}
