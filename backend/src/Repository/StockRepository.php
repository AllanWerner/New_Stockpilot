<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Boutique;
use App\Entity\Produit;
use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stock>
 */
final class StockRepository extends ServiceEntityRepository implements StockRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    public function findSousSeuil(Boutique $boutique): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.boutique = :boutique')
            ->andWhere('s.quantiteActuelle <= s.seuilReappro')
            ->setParameter('boutique', $boutique)
            ->orderBy('s.quantiteActuelle', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findParBoutiques(array $boutiques): array
    {
        if ([] === $boutiques) {
            return [];
        }

        return $this->createQueryBuilder('s')
            ->addSelect('p')
            ->innerJoin('s.produit', 'p')
            ->andWhere('s.boutique IN (:boutiques)')
            ->setParameter('boutiques', $boutiques)
            ->getQuery()
            ->getResult();
    }

    public function findOneByProduitAndBoutique(Produit $produit, Boutique $boutique): ?Stock
    {
        return $this->findOneBy(['produit' => $produit, 'boutique' => $boutique]);
    }

    public function save(Stock $stock): void
    {
        $em = $this->getEntityManager();
        $em->persist($stock);
        $em->flush();
    }
}
