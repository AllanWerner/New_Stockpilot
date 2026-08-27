<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MouvementStock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MouvementStock>
 */
final class MouvementStockRepository extends ServiceEntityRepository implements MouvementStockRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MouvementStock::class);
    }

    public function save(MouvementStock $mouvement): void
    {
        $em = $this->getEntityManager();
        $em->persist($mouvement);
        $em->flush();
    }

    public function findDepuisPourBoutiques(array $boutiques, \DateTimeImmutable $depuis): array
    {
        if ([] === $boutiques) {
            return [];
        }

        return $this->createQueryBuilder('m')
            ->addSelect('p')
            ->innerJoin('m.produit', 'p')
            ->andWhere('m.boutique IN (:boutiques)')
            ->andWhere('m.dateMouvement >= :depuis')
            ->setParameter('boutiques', $boutiques)
            ->setParameter('depuis', $depuis)
            ->getQuery()
            ->getResult();
    }
}
