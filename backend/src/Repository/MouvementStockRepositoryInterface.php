<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Boutique;
use App\Entity\Enum\TypeMouvement;
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

    /**
     * @param Boutique[]      $boutiques
     * @param TypeMouvement[] $types
     *
     * @return MouvementStock[]
     */
    public function findParTypesPourBoutiques(array $boutiques, array $types): array;
}
