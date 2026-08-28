<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Boutique;
use App\Entity\Commande;
use App\Entity\Enum\StatutCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
final class CommandeRepository extends ServiceEntityRepository implements CommandeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Commande
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    public function findByBoutique(Boutique $boutique): array
    {
        return $this->findBy(['boutique' => $boutique], ['dateCreation' => 'DESC']);
    }

    public function countEnCoursPourBoutiques(array $boutiques): int
    {
        if ([] === $boutiques) {
            return 0;
        }

        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.boutique IN (:boutiques)')
            ->andWhere('c.statut IN (:statuts)')
            ->setParameter('boutiques', $boutiques)
            ->setParameter('statuts', [StatutCommande::ENVOYEE, StatutCommande::RECUE_PARTIELLE])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function save(Commande $commande): void
    {
        $em = $this->getEntityManager();
        $em->persist($commande);
        $em->flush();
    }
}
