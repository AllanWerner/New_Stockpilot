<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Categorie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Categorie>
 */
final class CategorieRepository extends ServiceEntityRepository implements CategorieRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Categorie::class);
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Categorie
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    public function findByNom(string $nom): ?Categorie
    {
        return $this->createQueryBuilder('c')
            ->andWhere('LOWER(c.nom) = LOWER(:nom)')
            ->setParameter('nom', $nom)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Categorie $categorie): void
    {
        $em = $this->getEntityManager();
        $em->persist($categorie);
        $em->flush();
    }
}
