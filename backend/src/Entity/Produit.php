<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\Table(name: 'produit')]
#[ORM\Index(columns: ['code_barre'], name: 'idx_produit_code_barre')]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_produit', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private string $nom;

    #[ORM\Column(name: 'code_barre', length: 30, unique: true, nullable: true)]
    private ?string $codeBarre = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'prix_achat', type: 'decimal', precision: 10, scale: 2)]
    private string $prixAchat;

    #[ORM\Column(type: 'text', options: ['default' => 'piece'])]
    private string $unite = 'piece';

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $quantite = 0;

    #[ORM\ManyToOne(targetEntity: Categorie::class)]
    #[ORM\JoinColumn(name: 'id_categorie', referencedColumnName: 'id_categorie', onDelete: 'RESTRICT', nullable: false)]
    private Categorie $categorie;

    /**
     * Not persisted (no ORM mapping): set transiently by ProduitService::creerDepuisScan()
     * so the API response can tell the Angular scan flow whether the product was
     * prefilled from the barcode lookup ('AUTOMATIQUE') or needs manual completion
     * ('MANUELLE'), per the Jalon 4 sequence diagram. Null outside that flow.
     */
    private ?string $sourceCompletion = null;

    public function __construct(string $nom, string $prixAchat, string $unite, Categorie $categorie, ?string $codeBarre = null)
    {
        $this->nom = $nom;
        $this->prixAchat = $prixAchat;
        $this->unite = $unite;
        $this->categorie = $categorie;
        $this->codeBarre = $codeBarre;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): void
    {
        $this->nom = $nom;
    }

    public function getCodeBarre(): ?string
    {
        return $this->codeBarre;
    }

    public function setCodeBarre(?string $codeBarre): void
    {
        $this->codeBarre = $codeBarre;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getPrixAchat(): string
    {
        return $this->prixAchat;
    }

    public function setPrixAchat(string $prixAchat): void
    {
        $this->prixAchat = $prixAchat;
    }

    public function getUnite(): string
    {
        return $this->unite;
    }

    public function setUnite(string $unite): void
    {
        $this->unite = $unite;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function getCategorie(): Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(Categorie $categorie): void
    {
        $this->categorie = $categorie;
    }

    public function getSourceCompletion(): ?string
    {
        return $this->sourceCompletion;
    }

    public function setSourceCompletion(?string $sourceCompletion): void
    {
        $this->sourceCompletion = $sourceCompletion;
    }
}
