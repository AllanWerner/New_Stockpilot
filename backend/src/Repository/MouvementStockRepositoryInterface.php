<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Boutique;
use App\Entity\MouvementStock;

interface MouvementStockRepositoryInterface
{
    public function save(MouvementStock $mouvement): void;

    /**
     * @param Boutique[] $boutiques
     *
     * @return MouvementStock[]
     */
    public function findDepuisPourBoutiques(array $boutiques, \DateTimeImmutable $depuis): array;
}
