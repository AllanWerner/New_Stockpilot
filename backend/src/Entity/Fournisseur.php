<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\FournisseurRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FournisseurRepository::class)]
#[ORM\Table(name: 'fournisseur')]
class Fournisseur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_fournisseur', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $nom;

    #[ORM\Column(name: 'email_contact', length: 255, nullable: true)]
    private ?string $emailContact = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(name: 'delai_livraison_jours', type: 'smallint', nullable: true)]
    private ?int $delaiLivraisonJours = null;

    public function __construct(string $nom)
    {
        $this->nom = $nom;
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

    public function getEmailContact(): ?string
    {
        return $this->emailContact;
    }

    public function setEmailContact(?string $emailContact): void
    {
        $this->emailContact = $emailContact;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): void
    {
        $this->telephone = $telephone;
    }

    public function getDelaiLivraisonJours(): ?int
    {
        return $this->delaiLivraisonJours;
    }

    public function setDelaiLivraisonJours(?int $delaiLivraisonJours): void
    {
        $this->delaiLivraisonJours = $delaiLivraisonJours;
    }
}
