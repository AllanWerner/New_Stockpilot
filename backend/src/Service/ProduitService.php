<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Request\CreateProduitRequestDto;
use App\Dto\Request\ProduitListRequestDto;
use App\Entity\Categorie;
use App\Entity\Produit;
use App\Repository\CategorieRepositoryInterface;
use App\Repository\ProduitRepositoryInterface;
use App\Service\Exception\ProduitDejaExistantException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ProduitService
{
    private const string CATEGORIE_PAR_DEFAUT = 'Non classé';

    public function __construct(
        private readonly ProduitRepositoryInterface $produitRepository,
        private readonly CategorieRepositoryInterface $categorieRepository,
        private readonly CodeBarreLookupService $codeBarreLookupService,
    ) {
    }

    public function creerDepuisScan(string $codeBarre): Produit
    {
        $this->refuserSiCodeBarreExistant($codeBarre);

        $infosExternes = $this->codeBarreLookupService->rechercherProduit($codeBarre);

        if (null !== $infosExternes) {
            $categorie = $this->trouverOuCreerCategorie($infosExternes->categorieSuggeree ?? self::CATEGORIE_PAR_DEFAUT);
            $produit = new Produit($infosExternes->nom, '0.00', 'piece', $categorie, $codeBarre);
            $produit->setSourceCompletion('AUTOMATIQUE');
        } else {
            $categorie = $this->trouverOuCreerCategorie(self::CATEGORIE_PAR_DEFAUT);
            $produit = new Produit('Produit à compléter', '0.00', 'piece', $categorie, $codeBarre);
            $produit->setSourceCompletion('MANUELLE');
        }

        $this->produitRepository->save($produit);

        return $produit;
    }

    public function creerManuel(CreateProduitRequestDto $dto): Produit
    {
        if (null !== $dto->codeBarre) {
            $this->refuserSiCodeBarreExistant($dto->codeBarre);
        }

        $categorie = $this->categorieRepository->find($dto->idCategorie);

        if (null === $categorie) {
            throw new NotFoundHttpException('Catégorie introuvable.');
        }

        $produit = new Produit($dto->nom, $dto->prixAchat, $dto->unite, $categorie, $dto->codeBarre);
        $produit->setDescription($dto->description);

        $this->produitRepository->save($produit);

        return $produit;
    }

    /**
     * @return Produit[]
     */
    public function rechercher(ProduitListRequestDto $dto): array
    {
        return $this->produitRepository->search($dto);
    }

    private function refuserSiCodeBarreExistant(string $codeBarre): void
    {
        $existant = $this->produitRepository->findByCodeBarre($codeBarre);

        if (null !== $existant) {
            throw new ProduitDejaExistantException($existant);
        }
    }

    private function trouverOuCreerCategorie(string $nom): Categorie
    {
        $categorie = $this->categorieRepository->findByNom($nom);

        if (null !== $categorie) {
            return $categorie;
        }

        $categorie = new Categorie($nom);
        $this->categorieRepository->save($categorie);

        return $categorie;
    }
}
