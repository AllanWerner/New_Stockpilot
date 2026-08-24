<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Boutique;
use App\Entity\Commande;
use App\Entity\Employe;
use App\Entity\Enum\TypeMouvement;
use App\Entity\Produit;
use App\Entity\Stock;
use App\Repository\StockRepositoryInterface;
use App\Service\Exception\StockInsuffisantException;

final class StockService
{
    public function __construct(
        private readonly StockRepositoryInterface $stockRepository,
        private readonly MouvementStockService $mouvementStockService,
    ) {
    }

    public function incrementerStock(
        Produit $produit,
        Boutique $boutique,
        int $quantite,
        TypeMouvement $type,
        Employe $employe,
        ?Commande $commande = null,
    ): void {
        if ($quantite <= 0) {
            throw new \InvalidArgumentException('La quantité doit être strictement positive.');
        }

        $stock = $this->obtenirOuCreerStock($produit, $boutique);
        $stock->setQuantiteActuelle($stock->getQuantiteActuelle() + $quantite);
        $this->stockRepository->save($stock);

        $this->mouvementStockService->enregistrerMouvement($type, $produit, $boutique, $quantite, $employe, $commande);
    }

    public function decrementerStock(
        Produit $produit,
        Boutique $boutique,
        int $quantite,
        TypeMouvement $type,
        Employe $employe,
        ?Commande $commande = null,
    ): void {
        if ($quantite <= 0) {
            throw new \InvalidArgumentException('La quantité doit être strictement positive.');
        }

        $stock = $this->obtenirOuCreerStock($produit, $boutique);
        $quantiteDisponible = $stock->getQuantiteActuelle();

        if ($quantite > $quantiteDisponible) {
            throw new StockInsuffisantException($quantite, $quantiteDisponible);
        }

        $stock->setQuantiteActuelle($quantiteDisponible - $quantite);
        $this->stockRepository->save($stock);

        $this->mouvementStockService->enregistrerMouvement($type, $produit, $boutique, -$quantite, $employe, $commande);
    }

    /**
     * @return Stock[]
     */
    public function listerSousSeuil(Boutique $boutique): array
    {
        return $this->stockRepository->findSousSeuil($boutique);
    }

    /**
     * Not on the Jalon 4 class diagram, added because creating a product and
     * setting its per-boutique reorder threshold are distinct CDCF actions —
     * see the plan's flagged gap L5. Creates the Stock row on first use.
     */
    public function definirSeuil(Produit $produit, Boutique $boutique, int $seuil, int $quantiteCommandeReco): Stock
    {
        $stock = $this->stockRepository->findOneByProduitAndBoutique($produit, $boutique)
            ?? new Stock($produit, $boutique);

        $stock->setSeuilReappro($seuil);
        $stock->setQuantiteCommandeReco($quantiteCommandeReco);
        $this->stockRepository->save($stock);

        return $stock;
    }

    private function obtenirOuCreerStock(Produit $produit, Boutique $boutique): Stock
    {
        return $this->stockRepository->findOneByProduitAndBoutique($produit, $boutique)
            ?? new Stock($produit, $boutique);
    }
}
