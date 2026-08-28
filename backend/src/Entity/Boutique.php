<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BoutiqueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BoutiqueRepository::class)]
#[ORM\Table(name: 'boutique')]
class Boutique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_boutique', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $nom;

    #[ORM\Column(length: 255)]
    private string $adresse;

    #[ORM\Column(length: 100)]
    private string $ville;

    #[ORM\Column(type: 'boolean')]
    private bool $actif = true;

    public function __construct(string $nom, string $adresse, string $ville)
    {
        $this->nom = $nom;
        $this->adresse = $adresse;
        $this->ville = $ville;
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

    public function getAdresse(): string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): void
    {
        $this->adresse = $adresse;
    }

    public function getVille(): string
    {
        return $this->ville;
    }

    public function setVille(string $ville): void
    {
        $this->ville = $ville;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): void
    {
        $this->actif = $actif;
    }
}
