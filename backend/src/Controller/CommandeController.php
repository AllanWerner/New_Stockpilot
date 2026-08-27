<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\CreateCommandeRequestDto;
use App\Dto\Request\ReceptionCommandeRequestDto;
use App\Entity\Boutique;
use App\Entity\Commande;
use App\Entity\Employe;
use App\Entity\LigneCommande;
use App\Repository\BoutiqueRepositoryInterface;
use App\Repository\CommandeRepositoryInterface;
use App\Repository\FournisseurRepositoryInterface;
use App\Repository\ProduitRepositoryInterface;
use App\Security\BoutiqueVoter;
use App\Service\CommandeService;
use App\Service\StockService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class CommandeController extends AbstractController
{
    public function __construct(
        private readonly CommandeService $commandeService,
        private readonly StockService $stockService,
        private readonly CommandeRepositoryInterface $commandeRepository,
        private readonly BoutiqueRepositoryInterface $boutiqueRepository,
        private readonly FournisseurRepositoryInterface $fournisseurRepository,
        private readonly ProduitRepositoryInterface $produitRepository,
    ) {
    }

    /**
     * Matches the Jalon 4 sequence diagram's exact route: the pre-filled
     * draft (produits sous seuil + quantité recommandée) that powers the
     * "Générer une commande" screen.
     */
    #[Route('/api/boutiques/{id}/produits-sous-seuil', methods: ['GET'])]
    public function produitsSousSeuil(#[MapEntity(id: 'id')] Boutique $boutique): JsonResponse
    {
        $this->denyAccessUnlessGranted(BoutiqueVoter::ACCESS, $boutique);

        $stocks = $this->stockService->listerSousSeuil($boutique);

        return $this->json(array_map(static fn ($stock) => [
            'idProduit' => $stock->getProduit()->getId(),
            'nom' => $stock->getProduit()->getNom(),
            'quantiteActuelle' => $stock->getQuantiteActuelle(),
            'seuilReappro' => $stock->getSeuilReappro(),
            'quantiteRecommandee' => $stock->getQuantiteCommandeReco(),
            'prixAchat' => $stock->getProduit()->getPrixAchat(),
        ], $stocks));
    }

    #[Route('/api/commandes', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $idBoutique = $request->query->get('idBoutique');

        if (null === $idBoutique) {
            throw new BadRequestHttpException('Le paramètre idBoutique est requis.');
        }

        $boutique = $this->trouverBoutique((int) $idBoutique);
        $this->denyAccessUnlessGranted(BoutiqueVoter::ACCESS, $boutique);

        return $this->json(array_map(
            fn (Commande $c) => $this->commandeVersResume($c),
            $this->commandeRepository->findByBoutique($boutique),
        ));
    }

    #[Route('/api/commandes/{id}', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $commande = $this->trouverCommande($id);
        $this->denyAccessUnlessGranted(BoutiqueVoter::ACCESS, $commande->getBoutique());

        return $this->json($this->commandeVersReponse($commande));
    }

    #[Route('/api/commandes', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateCommandeRequestDto $dto): JsonResponse
    {
        $boutique = $this->trouverBoutique($dto->idBoutique);
        $this->denyAccessUnlessGranted(BoutiqueVoter::ACCESS, $boutique);

        $fournisseur = $this->fournisseurRepository->find($dto->idFournisseur);

        if (null === $fournisseur) {
            throw new NotFoundHttpException('Fournisseur introuvable.');
        }

        $produitsParId = [];

        foreach ($dto->lignes as $ligneInput) {
            $produit = $this->produitRepository->find($ligneInput->idProduit);

            if (null === $produit) {
                throw new NotFoundHttpException(sprintf('Produit #%d introuvable.', $ligneInput->idProduit));
            }

            $produitsParId[$ligneInput->idProduit] = $produit;
        }

        /** @var Employe $employe */
        $employe = $this->getUser();
        $datePrevue = null !== $dto->datePrevue ? new \DateTimeImmutable($dto->datePrevue) : null;

        $commande = $this->commandeService->creerEtEnvoyer(
            $boutique,
            $fournisseur,
            $employe,
            $dto->lignes,
            $produitsParId,
            $datePrevue,
        );

        return $this->json($this->commandeVersReponse($commande), 201);
    }

    #[Route('/api/commandes/{id}/reception', methods: ['POST'])]
    public function reception(int $id, #[MapRequestPayload] ReceptionCommandeRequestDto $dto): JsonResponse
    {
        $commande = $this->trouverCommande($id);
        $this->denyAccessUnlessGranted(BoutiqueVoter::ACCESS, $commande->getBoutique());

        /** @var Employe $employe */
        $employe = $this->getUser();

        $commande = $this->commandeService->receptionner($commande, $dto->lignes, $employe);

        return $this->json($this->commandeVersReponse($commande));
    }

    private function trouverBoutique(int $id): Boutique
    {
        $boutique = $this->boutiqueRepository->find($id);

        if (null === $boutique) {
            throw new NotFoundHttpException('Boutique introuvable.');
        }

        return $boutique;
    }

    private function trouverCommande(int $id): Commande
    {
        $commande = $this->commandeRepository->find($id);

        if (null === $commande) {
            throw new NotFoundHttpException('Commande introuvable.');
        }

        return $commande;
    }

    /**
     * @return array<string, mixed>
     */
    private function commandeVersResume(Commande $commande): array
    {
        return [
            'idCommande' => $commande->getId(),
            'statut' => $commande->getStatut()->value,
            'dateCreation' => $commande->getDateCreation()->format(\DateTimeInterface::ATOM),
            'datePrevue' => $commande->getDatePrevue()?->format('Y-m-d'),
            'fournisseur' => [
                'idFournisseur' => $commande->getFournisseur()->getId(),
                'nom' => $commande->getFournisseur()->getNom(),
            ],
            'boutique' => [
                'idBoutique' => $commande->getBoutique()->getId(),
                'nom' => $commande->getBoutique()->getNom(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function commandeVersReponse(Commande $commande): array
    {
        $resume = $this->commandeVersResume($commande);

        $resume['lignes'] = array_map(static function (LigneCommande $ligne): array {
            $produit = $ligne->getProduit();
            $sousTotal = number_format($ligne->getQuantiteCommandee() * (float) $ligne->getPrixUnitaire(), 2, '.', '');

            return [
                'idLigneCommande' => $ligne->getId(),
                'produit' => ['idProduit' => $produit->getId(), 'nom' => $produit->getNom()],
                'quantiteCommandee' => $ligne->getQuantiteCommandee(),
                'quantiteRecue' => $ligne->getQuantiteRecue(),
                'prixUnitaire' => $ligne->getPrixUnitaire(),
                'sousTotal' => $sousTotal,
            ];
        }, $commande->getLignes()->toArray());

        return $resume;
    }
}
