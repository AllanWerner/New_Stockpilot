<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\CreateCategorieRequestDto;
use App\Entity\Categorie;
use App\Repository\CategorieRepositoryInterface;
use App\Service\Exception\CategorieDejaExistanteException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/categories')]
final class CategorieController extends AbstractController
{
    public function __construct(private readonly CategorieRepositoryInterface $categorieRepository)
    {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json(array_map(
            fn (Categorie $c) => $this->categorieVersReponse($c),
            $this->categorieRepository->findAll(),
        ));
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_GERANT')]
    public function create(#[MapRequestPayload] CreateCategorieRequestDto $dto): JsonResponse
    {
        if (null !== $this->categorieRepository->findByNom($dto->nom)) {
            throw new CategorieDejaExistanteException();
        }

        $categorie = new Categorie($dto->nom);
        $this->categorieRepository->save($categorie);

        return $this->json($this->categorieVersReponse($categorie), 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function categorieVersReponse(Categorie $categorie): array
    {
        return [
            'idCategorie' => $categorie->getId(),
            'nom' => $categorie->getNom(),
        ];
    }
}
