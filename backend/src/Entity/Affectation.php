<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enum\PosteEmploye;
use App\Repository\AffectationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AffectationRepository::class)]
#[ORM\Table(name: 'affecter')]
class Affectation
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Employe::class)]
    #[ORM\JoinColumn(name: 'id_employe', referencedColumnName: 'id_employe', onDelete: 'CASCADE')]
    private Employe $employe;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Boutique::class)]
    #[ORM\JoinColumn(name: 'id_boutique', referencedColumnName: 'id_boutique', onDelete: 'CASCADE')]
    private Boutique $boutique;

    #[ORM\Column(name: 'poste_employe', type: 'poste_employe')]
    private PosteEmploye $posteEmploye;

    public function __construct(Employe $employe, Boutique $boutique, PosteEmploye $posteEmploye)
    {
        $this->employe = $employe;
        $this->boutique = $boutique;
        $this->posteEmploye = $posteEmploye;
    }

    public function getEmploye(): Employe
    {
        return $this->employe;
    }

    public function getBoutique(): Boutique
    {
        return $this->boutique;
    }

    public function getPosteEmploye(): PosteEmploye
    {
        return $this->posteEmploye;
    }

    public function setPosteEmploye(PosteEmploye $posteEmploye): void
    {
        $this->posteEmploye = $posteEmploye;
    }
}
