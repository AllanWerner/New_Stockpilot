<?php

declare(strict_types=1);

namespace App\Service\Exception;

use App\Entity\Produit;

final class ProduitDejaExistantException extends \RuntimeException
{
    public function __construct(public readonly Produit $produit)
    {
        parent::__construct('Un produit avec ce code-barres existe déjà.');
    }
}
