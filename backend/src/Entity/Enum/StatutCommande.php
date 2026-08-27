<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum StatutCommande: string
{
    case BROUILLON = 'BROUILLON';
    case ENVOYEE = 'ENVOYEE';
    case RECUE_PARTIELLE = 'RECUE_PARTIELLE';
    case RECUE = 'RECUE';
}
