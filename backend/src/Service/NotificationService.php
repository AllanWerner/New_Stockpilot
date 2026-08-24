<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Commande;
use App\Entity\Employe;
use App\Entity\Enum\StatutCommande;
use App\Entity\Notification;
use App\Repository\EmployeRepositoryInterface;
use App\Repository\NotificationRepositoryInterface;

/**
 * F3 scope only (notifierReception): alerterSeuilCritique from the Jalon 4
 * class diagram is F5's, added when notifications/alerting land.
 */
final class NotificationService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
        private readonly EmployeRepositoryInterface $employeRepository,
    ) {
    }

    public function notifierReception(Commande $commande, StatutCommande $statut, Employe $employeReception): void
    {
        $message = sprintf(
            'Commande #%d (%s) — %s.',
            $commande->getId(),
            $commande->getBoutique()->getNom(),
            $this->libelleStatut($statut),
        );

        $destinataires = $this->employeRepository->findGerants();
        $destinataires[] = $employeReception;

        $dejaNotifies = [];

        foreach ($destinataires as $employe) {
            if (isset($dejaNotifies[$employe->getId()])) {
                continue;
            }

            $dejaNotifies[$employe->getId()] = true;
            $this->notificationRepository->save(new Notification('RECEPTION_COMMANDE', $message, $employe));
        }
    }

    private function libelleStatut(StatutCommande $statut): string
    {
        return match ($statut) {
            StatutCommande::RECUE => 'reçue intégralement',
            StatutCommande::RECUE_PARTIELLE => 'reçue partiellement',
            default => $statut->value,
        };
    }
}
