<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enum\StatutCommande;
use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\Table(name: 'commande')]
#[ORM\Index(columns: ['statut'], name: 'idx_commande_statut')]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_commande', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'statut_commande')]
    private StatutCommande $statut = StatutCommande::BROUILLON;

    #[ORM\Column(name: 'date_creation', type: 'datetime_immutable')]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(name: 'date_prevue', type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $datePrevue = null;

    #[ORM\ManyToOne(targetEntity: Boutique::class)]
    #[ORM\JoinColumn(name: 'id_boutique', referencedColumnName: 'id_boutique', onDelete: 'RESTRICT', nullable: false)]
    private Boutique $boutique;

    #[ORM\ManyToOne(targetEntity: Fournisseur::class)]
    #[ORM\JoinColumn(name: 'id_fournisseur', referencedColumnName: 'id_fournisseur', onDelete: 'RESTRICT', nullable: false)]
    private Fournisseur $fournisseur;

    #[ORM\ManyToOne(targetEntity: Employe::class)]
    #[ORM\JoinColumn(name: 'id_employe', referencedColumnName: 'id_employe', onDelete: 'RESTRICT', nullable: false)]
    private Employe $employe;

    /**
     * @var Collection<int, LigneCommande>
     */
    #[ORM\OneToMany(mappedBy: 'commande', targetEntity: LigneCommande::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $lignes;

    public function __construct(Boutique $boutique, Fournisseur $fournisseur, Employe $employe, ?\DateTimeImmutable $datePrevue = null)
    {
        $this->boutique = $boutique;
        $this->fournisseur = $fournisseur;
        $this->employe = $employe;
        $this->datePrevue = $datePrevue;
        $this->dateCreation = new \DateTimeImmutable();
        $this->lignes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatut(): StatutCommande
    {
        return $this->statut;
    }

    public function setStatut(StatutCommande $statut): void
    {
        $this->statut = $statut;
    }

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function getDatePrevue(): ?\DateTimeImmutable
    {
        return $this->datePrevue;
    }

    public function setDatePrevue(?\DateTimeImmutable $datePrevue): void
    {
        $this->datePrevue = $datePrevue;
    }

    public function getBoutique(): Boutique
    {
        return $this->boutique;
    }

    public function getFournisseur(): Fournisseur
    {
        return $this->fournisseur;
    }

    public function getEmploye(): Employe
    {
        return $this->employe;
    }

    /**
     * @return Collection<int, LigneCommande>
     */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function ajouterLigne(LigneCommande $ligne): void
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes->add($ligne);
            $ligne->setCommande($this);
        }
    }
}
