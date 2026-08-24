<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LigneCommandeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LigneCommandeRepository::class)]
#[ORM\Table(name: 'ligne_commande')]
class LigneCommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_ligne_commande', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Commande::class, inversedBy: 'lignes')]
    #[ORM\JoinColumn(name: 'id_commande', referencedColumnName: 'id_commande', onDelete: 'CASCADE', nullable: false)]
    private Commande $commande;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: 'id_produit', referencedColumnName: 'id_produit', onDelete: 'RESTRICT', nullable: false)]
    private Produit $produit;

    #[ORM\Column(name: 'quantite_commandee', type: 'integer')]
    private int $quantiteCommandee;

    #[ORM\Column(name: 'quantite_recue', type: 'integer', options: ['default' => 0])]
    private int $quantiteRecue = 0;

    #[ORM\Column(name: 'prix_unitaire', type: 'decimal', precision: 10, scale: 2)]
    private string $prixUnitaire;

    public function __construct(Commande $commande, Produit $produit, int $quantiteCommandee, string $prixUnitaire)
    {
        if ($quantiteCommandee <= 0) {
            throw new \InvalidArgumentException('La quantité commandée doit être strictement positive.');
        }

        $this->commande = $commande;
        $this->produit = $produit;
        $this->quantiteCommandee = $quantiteCommandee;
        $this->prixUnitaire = $prixUnitaire;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): Commande
    {
        return $this->commande;
    }

    public function setCommande(Commande $commande): void
    {
        $this->commande = $commande;
    }

    public function getProduit(): Produit
    {
        return $this->produit;
    }

    public function getQuantiteCommandee(): int
    {
        return $this->quantiteCommandee;
    }

    public function getQuantiteRecue(): int
    {
        return $this->quantiteRecue;
    }

    public function setQuantiteRecue(int $quantiteRecue): void
    {
        if ($quantiteRecue < 0) {
            throw new \InvalidArgumentException('La quantité reçue ne peut pas être négative.');
        }

        $this->quantiteRecue = $quantiteRecue;
    }

    public function getPrixUnitaire(): string
    {
        return $this->prixUnitaire;
    }
}
