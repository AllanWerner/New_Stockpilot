<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Employe;
use App\Entity\Enum\RoleEmploye;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Employe>
 */
final class EmployeRepository extends ServiceEntityRepository implements EmployeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employe::class);
    }

    public function findByEmail(string $email): ?Employe
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Employe
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    public function findGerants(): array
    {
        return $this->findBy(['role' => RoleEmploye::GERANT]);
    }

    public function save(Employe $employe): void
    {
        $em = $this->getEntityManager();
        $em->persist($employe);
        $em->flush();
    }

    public function delete(Employe $employe): void
    {
        $em = $this->getEntityManager();
        $em->remove($employe);
        $em->flush();
    }
}
