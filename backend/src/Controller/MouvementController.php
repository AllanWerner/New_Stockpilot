<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Enum\TypeMouvement;
use App\Entity\MouvementStock;
use App\Repository\BoutiqueRepositoryInterface;
use App\Repository\MouvementStockRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Historique des mouvements (F2, "Manager Only") — surfaces only RECEPTION
 * and TRANSFERT movements, matching the CDCF's "réceptions et transferts"
 * framing. VENTE is out of scope (see ProduitController::ajuster()'s
 * comment) and AJUSTEMENT is a separate inventory-correction concern, not a
 * history item a gérant needs to audit here.
 */
#[Route('/api/mouvements')]
final class MouvementController extends AbstractController
{
    private const array TYPES_HISTORIQUE = [TypeMouvement::RECEPTION, TypeMouvement::TRANSFERT];

    public function __construct(
        private readonly MouvementStockRepositoryInterface $mouvementStockRepository,
        private readonly BoutiqueRepositoryInterface $boutiqueRepository,
    ) {
    }

    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_GERANT')]
    public function list(Request $request): JsonResponse
    {
        $idBoutique = $request->query->get('idBoutique');

        if (null !== $idBoutique) {
            $boutique = $this->boutiqueRepository->find((int) $idBoutique);

            if (null === $boutique) {
                throw new NotFoundHttpException('Boutique introuvable.');
            }

            $boutiques = [$boutique];
        } else {
            $boutiques = $this->boutiqueRepository->findAll();
        }

        $mouvements = $this->mouvementStockRepository->findParTypesPourBoutiques($boutiques, self::TYPES_HISTORIQUE);

        return $this->json(array_map(fn (MouvementStock $m) => $this->mouvementVersReponse($m), $mouvements));
    }

    /**
     * @return array<string, mixed>
     */
    private function mouvementVersReponse(MouvementStock $mouvement): array
    {
        $employe = $mouvement->getEmploye();
        $commande = $mouvement->getCommande();

        return [
            'idMouvement' => $mouvement->getId(),
            'type' => $mouvement->getType()->value,
            'quantite' => $mouvement->getQuantite(),
            'dateMouvement' => $mouvement->getDateMouvement()->format(DATE_ATOM),
            'produit' => [
                'idProduit' => $mouvement->getProduit()->getId(),
                'nom' => $mouvement->getProduit()->getNom(),
            ],
            'boutique' => [
                'idBoutique' => $mouvement->getBoutique()->getId(),
                'nom' => $mouvement->getBoutique()->getNom(),
            ],
            'employe' => [
                'idEmploye' => $employe->getId(),
                'nom' => $employe->getNom(),
                'prenom' => $employe->getPrenom(),
            ],
            'idCommande' => $commande?->getId(),
        ];
    }
}
