<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Boutique;
use App\Entity\Produit;
use App\Entity\Stock;

interface StockRepositoryInterface
{
    /**
     * @return Stock[]
     */
    public function findSousSeuil(Boutique $boutique): array;

    public function findOneByProduitAndBoutique(Produit $produit, Boutique $boutique): ?Stock;

    public function save(Stock $stock): void;
}
