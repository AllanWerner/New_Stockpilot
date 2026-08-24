<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Commande;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class EmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $emailExpediteur,
    ) {
    }

    public function envoyerEmailCommande(Commande $commande, string $emailFournisseur): void
    {
        $lignes = array_map(
            static fn ($ligne) => sprintf(
                '- %s : %d x %s €',
                $ligne->getProduit()->getNom(),
                $ligne->getQuantiteCommandee(),
                $ligne->getPrixUnitaire(),
            ),
            $commande->getLignes()->toArray(),
        );

        $email = (new Email())
            ->from($this->emailExpediteur)
            ->to($emailFournisseur)
            ->subject(sprintf('Nouvelle commande StockPilot #%d', $commande->getId()))
            ->text(sprintf(
                "Bonjour,\n\nUne nouvelle commande a été passée depuis la boutique %s :\n\n%s\n\nMerci de nous confirmer sa bonne réception.\n\nStockPilot",
                $commande->getBoutique()->getNom(),
                implode("\n", $lignes),
            ));

        $this->mailer->send($email);
    }
}
