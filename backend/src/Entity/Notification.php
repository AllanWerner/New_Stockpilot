<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
#[ORM\Index(columns: ['id_employe', 'lu'], name: 'idx_notification_employe_lu')]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_notification', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $type;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $lu = false;

    #[ORM\Column(name: 'date_creation', type: 'datetime_immutable')]
    private \DateTimeImmutable $dateCreation;

    #[ORM\ManyToOne(targetEntity: Employe::class)]
    #[ORM\JoinColumn(name: 'id_employe', referencedColumnName: 'id_employe', onDelete: 'CASCADE', nullable: false)]
    private Employe $employe;

    public function __construct(string $type, string $message, Employe $employe)
    {
        $this->type = $type;
        $this->message = $message;
        $this->employe = $employe;
        $this->dateCreation = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function isLu(): bool
    {
        return $this->lu;
    }

    public function marquerLue(): void
    {
        $this->lu = true;
    }

    public function marquerNonLue(): void
    {
        $this->lu = false;
    }

    public function getDateCreation(): \DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function getEmploye(): Employe
    {
        return $this->employe;
    }
}
