<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Dto\Request\CreateProduitRequestDto;
use App\Entity\Categorie;
use App\Entity\Produit;
use App\Repository\CategorieRepositoryInterface;
use App\Repository\ProduitRepositoryInterface;
use App\Service\CodeBarreLookupService;
use App\Service\Exception\ProduitDejaExistantException;
use App\Service\ProduitService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ProduitServiceTest extends TestCase
{
    private function offClientRenvoyant(array $payload, int $statusCode = 200): HttpClientInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('toArray')->willReturn($payload);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        return $httpClient;
    }

    public function testCreerDepuisScanRefuseSiCodeBarreDejaConnu(): void
    {
        $categorie = new Categorie('Épicerie');
        $existant = new Produit('Farine T65', '1.20', 'kg', $categorie, '1234567890123');

        $produitRepository = $this->createMock(ProduitRepositoryInterface::class);
        $produitRepository->method('findByCodeBarre')->willReturn($existant);

        $lookup = new CodeBarreLookupService($this->offClientRenvoyant([]), 'https://example.test');

        $service = new ProduitService($produitRepository, $this->createMock(CategorieRepositoryInterface::class), $lookup);

        $this->expectException(ProduitDejaExistantException::class);
        $service->creerDepuisScan('1234567890123');
    }

    public function testCreerDepuisScanAvecReferenceTrouveeEstAutomatique(): void
    {
        $produitRepository = $this->createMock(ProduitRepositoryInterface::class);
        $produitRepository->method('findByCodeBarre')->willReturn(null);
        $produitRepository->expects($this->once())->method('save');

        $categorieRepository = $this->createMock(CategorieRepositoryInterface::class);
        $categorieRepository->method('findByNom')->willReturn(null);
        $categorieRepository->expects($this->once())->method('save');

        $lookup = new CodeBarreLookupService(
            $this->offClientRenvoyant([
                'status' => 1,
                'product' => ['product_name' => 'Farine T65', 'categories' => 'Farines,Épicerie'],
            ]),
            'https://example.test',
        );

        $service = new ProduitService($produitRepository, $categorieRepository, $lookup);
        $produit = $service->creerDepuisScan('1234567890123');

        $this->assertSame('Farine T65', $produit->getNom());
        $this->assertSame('AUTOMATIQUE', $produit->getSourceCompletion());
    }

    public function testCreerDepuisScanSansReferenceEstManuelle(): void
    {
        $produitRepository = $this->createMock(ProduitRepositoryInterface::class);
        $produitRepository->method('findByCodeBarre')->willReturn(null);
        $produitRepository->expects($this->once())->method('save');

        $categorieRepository = $this->createMock(CategorieRepositoryInterface::class);
        $categorieRepository->method('findByNom')->willReturn(new Categorie('Non classé'));

        $lookup = new CodeBarreLookupService($this->offClientRenvoyant(['status' => 0]), 'https://example.test');

        $service = new ProduitService($produitRepository, $categorieRepository, $lookup);
        $produit = $service->creerDepuisScan('0000000000000');

        $this->assertSame('MANUELLE', $produit->getSourceCompletion());
    }

    public function testCreerManuelUtiliseLaCategorieExistante(): void
    {
        $categorie = new Categorie('Épicerie');

        $produitRepository = $this->createMock(ProduitRepositoryInterface::class);
        $produitRepository->method('findByCodeBarre')->willReturn(null);
        $produitRepository->expects($this->once())->method('save');

        $categorieRepository = $this->createMock(CategorieRepositoryInterface::class);
        $categorieRepository->expects($this->once())->method('find')->with(7)->willReturn($categorie);

        $lookup = new CodeBarreLookupService($this->offClientRenvoyant([]), 'https://example.test');
        $service = new ProduitService($produitRepository, $categorieRepository, $lookup);

        $dto = new CreateProduitRequestDto();
        $dto->nom = 'Café en grain 250g';
        $dto->prixAchat = '4.50';
        $dto->unite = 'piece';
        $dto->idCategorie = 7;

        $produit = $service->creerManuel($dto);

        $this->assertSame('Café en grain 250g', $produit->getNom());
        $this->assertSame($categorie, $produit->getCategorie());
    }
}
