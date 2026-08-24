<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProposerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProposerRepository::class)]
#[ORM\Table(name: 'proposer')]
class Proposer
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Fournisseur::class)]
    #[ORM\JoinColumn(name: 'id_fournisseur', referencedColumnName: 'id_fournisseur', onDelete: 'CASCADE')]
    private Fournisseur $fournisseur;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: 'id_produit', referencedColumnName: 'id_produit', onDelete: 'CASCADE')]
    private Produit $produit;

    #[ORM\Column(name: 'reference_fournisseur', length: 100, nullable: true)]
    private ?string $referenceFournisseur = null;

    #[ORM\Column(name: 'prix_fournisseur', type: 'decimal', precision: 10, scale: 2)]
    private string $prixFournisseur;

    public function __construct(Fournisseur $fournisseur, Produit $produit, string $prixFournisseur, ?string $referenceFournisseur = null)
    {
        $this->fournisseur = $fournisseur;
        $this->produit = $produit;
        $this->prixFournisseur = $prixFournisseur;
        $this->referenceFournisseur = $referenceFournisseur;
    }

    public function getFournisseur(): Fournisseur
    {
        return $this->fournisseur;
    }

    public function getProduit(): Produit
    {
        return $this->produit;
    }

    public function getReferenceFournisseur(): ?string
    {
        return $this->referenceFournisseur;
    }

    public function setReferenceFournisseur(?string $referenceFournisseur): void
    {
        $this->referenceFournisseur = $referenceFournisseur;
    }

    public function getPrixFournisseur(): string
    {
        return $this->prixFournisseur;
    }

    public function setPrixFournisseur(string $prixFournisseur): void
    {
        $this->prixFournisseur = $prixFournisseur;
    }
}
