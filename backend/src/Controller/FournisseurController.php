<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\Request\CreateFournisseurRequestDto;
use App\Entity\Fournisseur;
use App\Repository\FournisseurRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/fournisseurs')]
final class FournisseurController extends AbstractController
{
    public function __construct(private readonly FournisseurRepositoryInterface $fournisseurRepository)
    {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json(array_map(
            fn (Fournisseur $f) => $this->fournisseurVersReponse($f),
            $this->fournisseurRepository->findAll(),
        ));
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_GERANT')]
    public function create(#[MapRequestPayload] CreateFournisseurRequestDto $dto): JsonResponse
    {
        $fournisseur = new Fournisseur($dto->nom);
        $fournisseur->setEmailContact($dto->emailContact);
        $fournisseur->setTelephone($dto->telephone);
        $fournisseur->setDelaiLivraisonJours($dto->delaiLivraisonJours);

        $this->fournisseurRepository->save($fournisseur);

        return $this->json($this->fournisseurVersReponse($fournisseur), 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function fournisseurVersReponse(Fournisseur $fournisseur): array
    {
        return [
            'idFournisseur' => $fournisseur->getId(),
            'nom' => $fournisseur->getNom(),
            'emailContact' => $fournisseur->getEmailContact(),
            'telephone' => $fournisseur->getTelephone(),
            'delaiLivraisonJours' => $fournisseur->getDelaiLivraisonJours(),
        ];
    }
}
