<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Boutique;
use App\Entity\Commande;
use App\Entity\Employe;
use App\Entity\Enum\TypeMouvement;
use App\Entity\MouvementStock;
use App\Entity\Produit;
use App\Repository\MouvementStockRepositoryInterface;

final class MouvementStockService
{
    public function __construct(private readonly MouvementStockRepositoryInterface $mouvementStockRepository)
    {
    }

    public function enregistrerMouvement(
        TypeMouvement $type,
        Produit $produit,
        Boutique $boutique,
        int $quantite,
        Employe $employe,
        ?Commande $commande = null,
    ): MouvementStock {
        $mouvement = new MouvementStock($type, $quantite, $produit, $boutique, $employe, $commande);
        $this->mouvementStockRepository->save($mouvement);

        return $mouvement;
    }
}
