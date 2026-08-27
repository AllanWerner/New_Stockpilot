<?php

declare(strict_types=1);

namespace App\Doctrine\Type;

use App\Entity\Enum\StatutCommande;

final class StatutCommandeType extends AbstractPgEnumType
{
    protected function enumClass(): string
    {
        return StatutCommande::class;
    }

    protected function typeName(): string
    {
        return 'statut_commande';
    }
}
