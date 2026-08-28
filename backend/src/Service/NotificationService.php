<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Commande;
use App\Entity\Employe;
use App\Entity\Enum\StatutCommande;
use App\Entity\Notification;
use App\Entity\Stock;
use App\Repository\EmployeRepositoryInterface;
use App\Repository\NotificationRepositoryInterface;

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

    /**
     * Notifies every gérant when a stock movement makes a product cross under
     * its reorder threshold (see StockService::decrementerStock, which only
     * calls this on the crossing, not on every subsequent sale while already
     * low — otherwise a slow-moving low-stock product would spam alerts).
     */
    public function alerterSeuilCritique(Stock $stock): void
    {
        $message = sprintf(
            'Le produit "%s" (%s) a atteint son seuil de réapprovisionnement : %d unité(s) restante(s) (seuil : %d).',
            $stock->getProduit()->getNom(),
            $stock->getBoutique()->getNom(),
            $stock->getQuantiteActuelle(),
            $stock->getSeuilReappro(),
        );

        foreach ($this->employeRepository->findGerants() as $gerant) {
            $this->notificationRepository->save(new Notification('SEUIL_CRITIQUE', $message, $gerant));
        }
    }

    /**
     * Notifies every gérant, plus the employee who made the correction (if
     * not already a gérant) — mirrors notifierReception()'s
     * "gérants + acting employee, deduplicated" audience.
     */
    public function notifierAjustement(Stock $stock, int $quantite, Employe $employeAjustement): void
    {
        $message = sprintf(
            'Ajustement de stock : "%s" (%s) — %s%d unité(s), nouveau stock : %d.',
            $stock->getProduit()->getNom(),
            $stock->getBoutique()->getNom(),
            $quantite > 0 ? '+' : '',
            $quantite,
            $stock->getQuantiteActuelle(),
        );

        $destinataires = $this->employeRepository->findGerants();
        $destinataires[] = $employeAjustement;

        $dejaNotifies = [];

        foreach ($destinataires as $employe) {
            if (isset($dejaNotifies[$employe->getId()])) {
                continue;
            }

            $dejaNotifies[$employe->getId()] = true;
            $this->notificationRepository->save(new Notification('AJUSTEMENT_STOCK', $message, $employe));
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
