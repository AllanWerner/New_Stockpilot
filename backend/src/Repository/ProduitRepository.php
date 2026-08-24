<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\Request\ProduitListRequestDto;
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

    public function search(ProduitListRequestDto $criteres): array
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.nom', 'ASC');

        if (null !== $criteres->nom) {
            $qb->andWhere('LOWER(p.nom) LIKE LOWER(:nom)')
                ->setParameter('nom', '%'.$criteres->nom.'%');
        }

        if (null !== $criteres->idCategorie) {
            $qb->andWhere('p.categorie = :idCategorie')
                ->setParameter('idCategorie', $criteres->idCategorie);
        }

        if (null !== $criteres->idFournisseur) {
            $qb->innerJoin('App\Entity\Proposer', 'pr', 'WITH', 'pr.produit = p')
                ->andWhere('pr.fournisseur = :idFournisseur')
                ->setParameter('idFournisseur', $criteres->idFournisseur);
        }

        if (null !== $criteres->idBoutique) {
            $qb->innerJoin('App\Entity\Stock', 's', 'WITH', 's.produit = p AND s.boutique = :idBoutique')
                ->setParameter('idBoutique', $criteres->idBoutique);

            if ('rupture' === $criteres->statutStock) {
                $qb->andWhere('s.quantiteActuelle = 0');
            } elseif ('critique' === $criteres->statutStock) {
                $qb->andWhere('s.quantiteActuelle > 0 AND s.quantiteActuelle <= s.seuilReappro');
            } elseif ('ok' === $criteres->statutStock) {
                $qb->andWhere('s.quantiteActuelle > s.seuilReappro');
            }
        }

        return $qb
            ->setFirstResult(($criteres->page - 1) * $criteres->limit)
            ->setMaxResults($criteres->limit)
            ->getQuery()
            ->getResult();
    }

    public function save(Produit $produit): void
    {
        $em = $this->getEntityManager();
        $em->persist($produit);
        $em->flush();
    }
}
