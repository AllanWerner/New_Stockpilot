<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\DashboardDto;
use App\Dto\Response\PointValorisationDto;
use App\Dto\Response\ProduitSousSeuilCritiqueDto;
use App\Entity\Boutique;
use App\Entity\Employe;
use App\Entity\Stock;
use App\Repository\BoutiqueRepositoryInterface;
use App\Repository\CommandeRepositoryInterface;
use App\Repository\MouvementStockRepositoryInterface;
use App\Repository\StockRepositoryInterface;
use App\Security\AccessVoter;

/**
 * Builds the "Tableau de bord" screen data (Jalon 4 class diagram,
 * Figure 10 of the UI/UX methodology doc): 4 KPI cards, a valorization
 * trend over the last two weeks, and the sous-seuil-critique table.
 */
final class DashboardService
{
    private const JOURS_HISTORIQUE = 14;

    public function __construct(
        private readonly BoutiqueRepositoryInterface $boutiqueRepository,
        private readonly StockRepositoryInterface $stockRepository,
        private readonly MouvementStockRepositoryInterface $mouvementStockRepository,
        private readonly CommandeRepositoryInterface $commandeRepository,
        private readonly AccessVoter $accessVoter,
    ) {
    }

    public function consulterTableauDeBord(Employe $employe, ?Boutique $boutiqueFiltre = null): DashboardDto
    {
        $boutiques = null !== $boutiqueFiltre ? [$boutiqueFiltre] : $this->perimetreBoutiques($employe);
        $stocks = $this->stockRepository->findParBoutiques($boutiques);

        $valeurStock = 0.0;
        $referencesEnRupture = 0;
        $sousSeuilCritique = 0;
        $produitsSousSeuilCritique = [];

        foreach ($stocks as $stock) {
            $valeurStock += $stock->getQuantiteActuelle() * (float) $stock->getProduit()->getPrixAchat();

            if (0 === $stock->getQuantiteActuelle()) {
                ++$referencesEnRupture;
                $produitsSousSeuilCritique[] = $this->versSousSeuilDto($stock, 'rupture');
            } elseif ($stock->getQuantiteActuelle() <= $stock->getSeuilReappro()) {
                ++$sousSeuilCritique;
                $produitsSousSeuilCritique[] = $this->versSousSeuilDto($stock, 'critique');
            }
        }

        usort($produitsSousSeuilCritique, static fn ($a, $b) => $a->quantiteActuelle <=> $b->quantiteActuelle);

        return new DashboardDto(
            number_format($valeurStock, 2, '.', ''),
            $referencesEnRupture,
            $sousSeuilCritique,
            $this->commandeRepository->countEnCoursPourBoutiques($boutiques),
            $this->calculerEvolutionValorisation($boutiques, $valeurStock),
            $produitsSousSeuilCritique,
        );
    }

    /**
     * @return Boutique[]
     */
    private function perimetreBoutiques(Employe $employe): array
    {
        return $this->accessVoter->estGerant($employe)
            ? $this->boutiqueRepository->findAll()
            : $this->boutiqueRepository->findByEmploye($employe);
    }

    private function versSousSeuilDto(Stock $stock, string $statut): ProduitSousSeuilCritiqueDto
    {
        return new ProduitSousSeuilCritiqueDto(
            $stock->getProduit()->getId(),
            $stock->getProduit()->getNom(),
            $stock->getBoutique()->getId(),
            $stock->getBoutique()->getNom(),
            $stock->getQuantiteActuelle(),
            $stock->getSeuilReappro(),
            $statut,
        );
    }

    /**
     * Reconstructs a daily valorization trend by replaying signed stock
     * movements backward from today's total (current unit prices — there is
     * no historical price ledger, matching the CDCF's "évolution du stock"
     * framing rather than a true historical-cost valuation).
     *
     * @param Boutique[] $boutiques
     *
     * @return PointValorisationDto[]
     */
    private function calculerEvolutionValorisation(array $boutiques, float $valeurActuelle): array
    {
        $aujourdHui = new \DateTimeImmutable('today');
        $depuis = $aujourdHui->modify('-'.(self::JOURS_HISTORIQUE - 1).' days');

        $mouvements = $this->mouvementStockRepository->findDepuisPourBoutiques($boutiques, $depuis);

        $variationParJour = [];

        foreach ($mouvements as $mouvement) {
            $jour = $mouvement->getDateMouvement()->format('Y-m-d');
            $valeur = $mouvement->getQuantite() * (float) $mouvement->getProduit()->getPrixAchat();
            $variationParJour[$jour] = ($variationParJour[$jour] ?? 0.0) + $valeur;
        }

        $points = [];
        $valeurCourante = $valeurActuelle;

        for ($i = 0; $i < self::JOURS_HISTORIQUE; ++$i) {
            $jour = $aujourdHui->modify('-'.$i.' days')->format('Y-m-d');
            $points[] = new PointValorisationDto($jour, number_format($valeurCourante, 2, '.', ''));
            $valeurCourante -= $variationParJour[$jour] ?? 0.0;
        }

        return array_reverse($points);
    }
}
