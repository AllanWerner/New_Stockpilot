<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
final class ProduitRepository extends ServiceEntityRepository implements ProduitRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    public function findByCodeBarre(string $codeBarre): ?Produit
    {
        return $this->findOneBy(['codeBarre' => $codeBarre]);
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Produit
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    public function save(Produit $produit): void
    {
        $em = $this->getEntityManager();
        $em->persist($produit);
        $em->flush();
    }
}
