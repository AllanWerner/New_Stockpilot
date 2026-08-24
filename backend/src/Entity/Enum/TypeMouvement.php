<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum TypeMouvement: string
{
    case RECEPTION = 'RECEPTION';
    case VENTE = 'VENTE';
    case AJUSTEMENT = 'AJUSTEMENT';
    case TRANSFERT = 'TRANSFERT';
}
