<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StockRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Maps the "stocker" association: per-(produit,boutique) stock level and reorder threshold.
 */
#[ORM\Entity(repositoryClass: StockRepository::class)]
#[ORM\Table(name: 'stocker')]
class Stock
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: 'id_produit', referencedColumnName: 'id_produit', onDelete: 'CASCADE')]
    private Produit $produit;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Boutique::class)]
    #[ORM\JoinColumn(name: 'id_boutique', referencedColumnName: 'id_boutique', onDelete: 'CASCADE')]
    private Boutique $boutique;

    #[ORM\Column(name: 'quantite_actuelle', type: 'integer', options: ['default' => 0])]
    private int $quantiteActuelle = 0;

    #[ORM\Column(name: 'seuil_reappro', type: 'integer', options: ['default' => 0])]
    private int $seuilReappro = 0;

    #[ORM\Column(name: 'quantite_commande_reco', type: 'integer', options: ['default' => 0])]
    private int $quantiteCommandeReco = 0;

    public function __construct(Produit $produit, Boutique $boutique, int $seuilReappro = 0, int $quantiteCommandeReco = 0)
    {
        $this->produit = $produit;
        $this->boutique = $boutique;
        $this->seuilReappro = $seuilReappro;
        $this->quantiteCommandeReco = $quantiteCommandeReco;
    }

    public function getProduit(): Produit
    {
        return $this->produit;
    }

    public function getBoutique(): Boutique
    {
        return $this->boutique;
    }

    public function getQuantiteActuelle(): int
    {
        return $this->quantiteActuelle;
    }

    public function setQuantiteActuelle(int $quantiteActuelle): void
    {
        if ($quantiteActuelle < 0) {
            throw new \InvalidArgumentException('La quantité en stock ne peut pas être négative.');
        }

        $this->quantiteActuelle = $quantiteActuelle;
    }

    public function getSeuilReappro(): int
    {
        return $this->seuilReappro;
    }

    public function setSeuilReappro(int $seuilReappro): void
    {
        $this->seuilReappro = $seuilReappro;
    }

    public function getQuantiteCommandeReco(): int
    {
        return $this->quantiteCommandeReco;
    }

    public function setQuantiteCommandeReco(int $quantiteCommandeReco): void
    {
        $this->quantiteCommandeReco = $quantiteCommandeReco;
    }

    public function estSousSeuil(): bool
    {
        return $this->quantiteActuelle <= $this->seuilReappro;
    }
}
