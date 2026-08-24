<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Fournisseur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Fournisseur>
 */
final class FournisseurRepository extends ServiceEntityRepository implements FournisseurRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fournisseur::class);
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Fournisseur
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    public function findAll(): array
    {
        return parent::findAll();
    }

    public function save(Fournisseur $fournisseur): void
    {
        $em = $this->getEntityManager();
        $em->persist($fournisseur);
        $em->flush();
    }
}
