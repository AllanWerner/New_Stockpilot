<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Employe;
use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
final class NotificationRepository extends ServiceEntityRepository implements NotificationRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function save(Notification $notification): void
    {
        $em = $this->getEntityManager();
        $em->persist($notification);
        $em->flush();
    }

    public function find(mixed $id, LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?Notification
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    public function findByEmploye(Employe $employe): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.employe = :employe')
            ->setParameter('employe', $employe)
            ->orderBy('n.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countNonLuesPourEmploye(Employe $employe): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.employe = :employe')
            ->andWhere('n.lu = false')
            ->setParameter('employe', $employe)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function marquerToutesLues(Employe $employe): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.lu', ':lu')
            ->andWhere('n.employe = :employe')
            ->andWhere('n.lu = false')
            ->setParameter('lu', true)
            ->setParameter('employe', $employe)
            ->getQuery()
            ->execute();
    }
}
