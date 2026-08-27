<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Affectation;
use App\Entity\Boutique;
use App\Entity\Employe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Affectation>
 */
final class AffectationRepository extends ServiceEntityRepository implements AffectationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Affectation::class);
    }

    public function findOneByEmployeAndBoutique(Employe $employe, Boutique $boutique): ?Affectation
    {
        return $this->findOneBy(['employe' => $employe, 'boutique' => $boutique]);
    }

    public function findByEmploye(Employe $employe): array
    {
        return $this->findBy(['employe' => $employe]);
    }

    public function save(Affectation $affectation): void
    {
        $em = $this->getEntityManager();
        $em->persist($affectation);
        $em->flush();
    }
}
