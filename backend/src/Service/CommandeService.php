<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\LigneCommandeInputDto;
use App\Dto\Request\LigneReceptionInputDto;
use App\Entity\Boutique;
use App\Entity\Commande;
use App\Entity\Employe;
use App\Entity\Enum\StatutCommande;
use App\Entity\Enum\TypeMouvement;
use App\Entity\Fournisseur;
use App\Entity\LigneCommande;
use App\Entity\Produit;
use App\Repository\CommandeRepositoryInterface;
use App\Service\Exception\LigneCommandeIntrouvableException;
use App\Service\Exception\QuantiteRecueInvalideException;

/**
 * Orchestrates StockService, NotificationService and EmailService rather than
 * touching Stock/Notification entities directly (SRP, per the Jalon 4 class
 * diagram — StockService itself owns MouvementStockService).
 */
final class CommandeService
{
    public function __construct(
        private readonly CommandeRepositoryInterface $commandeRepository,
        private readonly StockService $stockService,
        private readonly NotificationService $notificationService,
        private readonly EmailService $emailService,
    ) {
    }

    /**
     * @param LigneCommandeInputDto[] $lignesInput
     * @param array<int, Produit>     $produitsParId indexed by idProduit
     */
    public function creerEtEnvoyer(
        Boutique $boutique,
        Fournisseur $fournisseur,
        Employe $employe,
        array $lignesInput,
        array $produitsParId,
        ?\DateTimeImmutable $datePrevue,
    ): Commande {
        $commande = new Commande($boutique, $fournisseur, $employe, $datePrevue);
        $commande->setStatut(StatutCommande::ENVOYEE);

        foreach ($lignesInput as $ligneInput) {
            $produit = $produitsParId[$ligneInput->idProduit];
            $ligne = new LigneCommande($commande, $produit, $ligneInput->quantiteCommandee, $ligneInput->prixUnitaire);
            $commande->ajouterLigne($ligne);
        }

        $this->commandeRepository->save($commande);

        $emailFournisseur = $fournisseur->getEmailContact();

        if (null !== $emailFournisseur) {
            $this->emailService->envoyerEmailCommande($commande, $emailFournisseur);
        }

        return $commande;
    }

    /**
     * @param LigneReceptionInputDto[] $lignesRecuesInput
     */
    public function receptionner(Commande $commande, array $lignesRecuesInput, Employe $employeReception): Commande
    {
        $lignesParId = [];

        foreach ($commande->getLignes() as $ligne) {
            $lignesParId[$ligne->getId()] = $ligne;
        }

        foreach ($lignesRecuesInput as $input) {
            $ligne = $lignesParId[$input->idLigneCommande] ?? null;

            if (null === $ligne) {
                throw new LigneCommandeIntrouvableException($input->idLigneCommande);
            }

            $nouvelleQuantiteRecue = $ligne->getQuantiteRecue() + $input->quantiteRecue;

            if ($nouvelleQuantiteRecue > $ligne->getQuantiteCommandee()) {
                throw new QuantiteRecueInvalideException($nouvelleQuantiteRecue, $ligne->getQuantiteCommandee());
            }

            $ligne->setQuantiteRecue($nouvelleQuantiteRecue);

            if ($input->quantiteRecue > 0) {
                $this->stockService->incrementerStock(
                    $ligne->getProduit(),
                    $commande->getBoutique(),
                    $input->quantiteRecue,
                    TypeMouvement::RECEPTION,
                    $employeReception,
                    $commande,
                );
            }
        }

        $statut = $this->determinerStatut($commande);
        $commande->setStatut($statut);
        $this->commandeRepository->save($commande);

        $this->notificationService->notifierReception($commande, $statut, $employeReception);

        return $commande;
    }

    private function determinerStatut(Commande $commande): StatutCommande
    {
        $toutesRecues = true;
        $auMoinsUneRecue = false;

        foreach ($commande->getLignes() as $ligne) {
            if ($ligne->getQuantiteRecue() > 0) {
                $auMoinsUneRecue = true;
            }

            if ($ligne->getQuantiteRecue() < $ligne->getQuantiteCommandee()) {
                $toutesRecues = false;
            }
        }

        if ($toutesRecues) {
            return StatutCommande::RECUE;
        }

        return $auMoinsUneRecue ? StatutCommande::RECUE_PARTIELLE : $commande->getStatut();
    }
}
