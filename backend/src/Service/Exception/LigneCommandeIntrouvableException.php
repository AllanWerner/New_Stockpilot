<?php

declare(strict_types=1);

namespace App\Service\Exception;

final class LigneCommandeIntrouvableException extends \RuntimeException
{
    public function __construct(int $idLigneCommande)
    {
        parent::__construct(sprintf('La ligne de commande #%d n\'existe pas sur cette commande.', $idLigneCommande));
    }
}
