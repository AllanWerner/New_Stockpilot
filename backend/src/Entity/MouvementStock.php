<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enum\TypeMouvement;
use App\Repository\MouvementStockRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MouvementStockRepository::class)]
#[ORM\Table(name: 'mouvement_stock')]
#[ORM\Index(columns: ['id_produit', 'id_boutique'], name: 'idx_mvt_produit_boutique')]
#[ORM\Index(columns: ['date_mouvement'], name: 'idx_mvt_date')]
class MouvementStock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_mouvement', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'type_mouvement')]
    private TypeMouvement $type;

    #[ORM\Column(type: 'integer')]
    private int $quantite;

    #[ORM\Column(name: 'date_mouvement', type: 'datetime_immutable')]
    private \DateTimeImmutable $dateMouvement;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: 'id_produit', referencedColumnName: 'id_produit', onDelete: 'RESTRICT', nullable: false)]
    private Produit $produit;

    #[ORM\ManyToOne(targetEntity: Boutique::class)]
    #[ORM\JoinColumn(name: 'id_boutique', referencedColumnName: 'id_boutique', onDelete: 'RESTRICT', nullable: false)]
    private Boutique $boutique;

    #[ORM\ManyToOne(targetEntity: Employe::class)]
    #[ORM\JoinColumn(name: 'id_employe', referencedColumnName: 'id_employe', onDelete: 'RESTRICT', nullable: false)]
    private Employe $employe;

    #[ORM\ManyToOne(targetEntity: Commande::class)]
    #[ORM\JoinColumn(name: 'id_commande', referencedColumnName: 'id_commande', onDelete: 'SET NULL', nullable: true)]
    private ?Commande $commande = null;

    public function __construct(
        TypeMouvement $type,
        int $quantite,
        Produit $produit,
        Boutique $boutique,
        Employe $employe,
        ?Commande $commande = null,
    ) {
        $this->type = $type;
        $this->quantite = $quantite;
        $this->produit = $produit;
        $this->boutique = $boutique;
        $this->employe = $employe;
        $this->commande = $commande;
        $this->dateMouvement = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): TypeMouvement
    {
        return $this->type;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function getDateMouvement(): \DateTimeImmutable
    {
        return $this->dateMouvement;
    }

    public function getProduit(): Produit
    {
        return $this->produit;
    }

    public function getBoutique(): Boutique
    {
        return $this->boutique;
    }

    public function getEmploye(): Employe
    {
        return $this->employe;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }
}
