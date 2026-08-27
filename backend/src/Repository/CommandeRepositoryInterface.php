<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Boutique;
use App\Entity\Commande;

interface CommandeRepositoryInterface
{
    public function find(int $id): ?Commande;

    /**
     * @return Commande[]
     */
    public function findByBoutique(Boutique $boutique): array;

    /**
     * @param Boutique[] $boutiques
     */
    public function countEnCoursPourBoutiques(array $boutiques): int;

    public function save(Commande $commande): void;
}
