<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Affectation;
use App\Entity\Boutique;
use App\Entity\Employe;

interface AffectationRepositoryInterface
{
    public function findOneByEmployeAndBoutique(Employe $employe, Boutique $boutique): ?Affectation;

    /**
     * @return Affectation[]
     */
    public function findByEmploye(Employe $employe): array;

    public function save(Affectation $affectation): void;
}
