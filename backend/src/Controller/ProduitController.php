<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\CreateProduitRequestDto;
use App\Dto\Request\ProduitListRequestDto;
use App\Dto\Request\ScanProduitRequestDto;
use App\Entity\Boutique;
use App\Entity\Produit;
use App\Repository\BoutiqueRepositoryInterface;
use App\Repository\StockRepositoryInterface;
use App\Security\BoutiqueVoter;
use App\Service\ProduitService;
use App\Service\StockService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/produits')]
final class ProduitController extends AbstractController
{
    public function __construct(
        private readonly ProduitService $produitService,
        private readonly StockService $stockService,
        private readonly BoutiqueRepositoryInterface $boutiqueRepository,
        private readonly StockRepositoryInterface $stockRepository,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $dto = ProduitListRequestDto::fromQuery($request->query->all());

        if (null !== $dto->idBoutique) {
            $this->denyAccessUnlessGranted(BoutiqueVoter::ACCESS, $this->trouverBoutique($dto->idBoutique));
        }

        $produits = $this->produitService->rechercher($dto);

        return $this->json([
            'items' => array_map(fn (Produit $p) => $this->produitVersReponse($p, $dto->idBoutique), $produits),
            'page' => $dto->page,
            'limit' => $dto->limit,
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateProduitRequestDto $dto): JsonResponse
    {
        $boutique = null !== $dto->idBoutique ? $this->trouverBoutique($dto->idBoutique) : null;

        if (null !== $boutique) {
            $this->denyAccessUnlessGranted(BoutiqueVoter::ACCESS, $boutique);
        }

        $produit = $this->produitService->creerManuel($dto);

        if (null !== $boutique) {
            $this->stockService->definirSeuil($produit, $boutique, $dto->seuilReappro, $dto->quantiteCommandeReco);
        }

        return $this->json($this->produitVersReponse($produit, $dto->idBoutique), 201);
    }

    #[Route('/scan', methods: ['POST'])]
    public function scan(#[MapRequestPayload] ScanProduitRequestDto $dto): JsonResponse
    {
        $boutique = null !== $dto->idBoutique ? $this->trouverBoutique($dto->idBoutique) : null;

        if (null !== $boutique) {
            $this->denyAccessUnlessGranted(BoutiqueVoter::ACCESS, $boutique);
        }

        $produit = $this->produitService->creerDepuisScan($dto->codeBarre);

        if (null !== $boutique) {
            $this->stockService->definirSeuil($produit, $boutique, $dto->seuilReappro, $dto->quantiteCommandeReco);
        }

        return $this->json($this->produitVersReponse($produit, $dto->idBoutique), 201);
    }

    private function trouverBoutique(int $id): Boutique
    {
        $boutique = $this->boutiqueRepository->find($id);

        if (null === $boutique) {
            throw new NotFoundHttpException('Boutique introuvable.');
        }

        return $boutique;
    }

    /**
     * @return array<string, mixed>
     */
    private function produitVersReponse(Produit $produit, ?int $idBoutique): array
    {
        $data = [
            'idProduit' => $produit->getId(),
            'nom' => $produit->getNom(),
            'codeBarre' => $produit->getCodeBarre(),
            'description' => $produit->getDescription(),
            'prixAchat' => $produit->getPrixAchat(),
            'unite' => $produit->getUnite(),
            'categorie' => [
                'idCategorie' => $produit->getCategorie()->getId(),
                'nom' => $produit->getCategorie()->getNom(),
            ],
            'sourceCompletion' => $produit->getSourceCompletion(),
        ];

        if (null !== $idBoutique) {
            $boutique = $this->boutiqueRepository->find($idBoutique);
            $stock = null !== $boutique ? $this->stockRepository->findOneByProduitAndBoutique($produit, $boutique) : null;

            $data['stock'] = null !== $stock ? [
                'quantiteActuelle' => $stock->getQuantiteActuelle(),
                'seuilReappro' => $stock->getSeuilReappro(),
                'quantiteCommandeReco' => $stock->getQuantiteCommandeReco(),
                'sousSeuil' => $stock->estSousSeuil(),
            ] : null;
        }

        return $data;
    }
}
