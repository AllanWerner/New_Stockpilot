<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Boutique;
use App\Entity\Employe;

interface BoutiqueRepositoryInterface
{
    /**
     * @return Boutique[]
     */
    public function findAll(): array;

    /**
     * Boutiques the given employee is affected to (irrelevant for a GERANT, who has
     * full access to every boutique — see AccessVoter).
     *
     * @return Boutique[]
     */
    public function findByEmploye(Employe $employe): array;

    public function find(int $id): ?Boutique;

    public function save(Boutique $boutique): void;
}
