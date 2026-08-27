<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Boutique;
use App\Entity\Employe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Boutique>
 */
final class BoutiqueRepository extends ServiceEntityRepository implements BoutiqueRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Boutique::class);
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function findByEmploye(Employe $employe): array
    {
        return $this->createQueryBuilder('b')
            ->innerJoin('App\Entity\Affectation', 'a', 'WITH', 'a.boutique = b')
            ->andWhere('a.employe = :employe')
            ->setParameter('employe', $employe)
            ->getQuery()
            ->getResult();
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Boutique
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    public function save(Boutique $boutique): void
    {
        $em = $this->getEntityManager();
        $em->persist($boutique);
        $em->flush();
    }
}
