<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enum\RoleEmploye;
use App\Repository\EmployeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: EmployeRepository::class)]
#[ORM\Table(name: 'employe')]
class Employe implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_employe', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $nom;

    #[ORM\Column(length: 100)]
    private string $prenom;

    #[ORM\Column(length: 255, unique: true)]
    private string $email;

    #[ORM\Column(name: 'mot_de_passe', length: 255)]
    private string $motDePasse;

    #[ORM\Column(type: 'role_employe')]
    private RoleEmploye $role;

    #[ORM\Column(name: 'date_creation', type: 'datetime_immutable')]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(type: 'boolean')]
    private bool $actif = true;

    public function __construct(string $nom, string $prenom, string $email, RoleEmploye $role)
    {
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->role = $role;
        $this->dateCreation = new \DateTimeImmutable();
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

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): void
    {
        $this->prenom = $prenom;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setMotDePasse(string $motDePasseHache): void
    {
        $this->motDePasse = $motDePasseHache;
    }

    public function getRole(): RoleEmploye
    {
        return $this->role;
    }

    public function setRole(RoleEmploye $role): void
    {
        $this->role = $role;
    }

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function isGerant(): bool
    {
        return RoleEmploye::GERANT === $this->role;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): void
    {
        $this->actif = $actif;
    }

    // --- Symfony Security: UserInterface / PasswordAuthenticatedUserInterface ---

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->motDePasse;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return RoleEmploye::GERANT === $this->role
            ? ['ROLE_GERANT', 'ROLE_USER']
            : ['ROLE_EMPLOYE', 'ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }
}
