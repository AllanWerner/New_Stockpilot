<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Boutique;
use App\Entity\Employe;
use App\Entity\Enum\PosteEmploye;
use App\Entity\Enum\RoleEmploye;
use App\Repository\AffectationRepositoryInterface;

/**
 * Central authorization logic (Jalon 4 class diagram). The DDL has no
 * gérant-owns-boutique table: a GERANT has full rights over every boutique in
 * the system; an EMPLOYE only has rights on boutiques they're affected to via
 * Affectation, elevated to RESPONSABLE-level rights only within that boutique.
 */
final class AccessVoter
{
    public function __construct(private readonly AffectationRepositoryInterface $affectationRepository)
    {
    }

    public function estGerant(Employe $employe): bool
    {
        return RoleEmploye::GERANT === $employe->getRole();
    }

    public function peutAccederBoutique(Employe $employe, Boutique $boutique): bool
    {
        if ($this->estGerant($employe)) {
            return true;
        }

        return null !== $this->affectationRepository->findOneByEmployeAndBoutique($employe, $boutique);
    }

    public function posteDans(Employe $employe, Boutique $boutique): ?PosteEmploye
    {
        $affectation = $this->affectationRepository->findOneByEmployeAndBoutique($employe, $boutique);

        return $affectation?->getPosteEmploye();
    }

    public function aDroitsElargis(Employe $employe, Boutique $boutique): bool
    {
        if ($this->estGerant($employe)) {
            return true;
        }

        return PosteEmploye::RESPONSABLE === $this->posteDans($employe, $boutique);
    }
}
