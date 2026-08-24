<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\Response\ProduitExterneDto;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Looks up a product by EAN barcode against the Open Food Facts public API
 * (no API key required — base URL still kept in an env var, never hardcoded).
 */
final class CodeBarreLookupService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $offApiBaseUrl,
    ) {
    }

    public function rechercherProduit(string $codeBarre): ?ProduitExterneDto
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                sprintf('%s/api/v2/product/%s.json', rtrim($this->offApiBaseUrl, '/'), $codeBarre),
            );

            if (404 === $response->getStatusCode()) {
                return null;
            }

            $data = $response->toArray(false);
        } catch (HttpClientExceptionInterface) {
            return null;
        }

        if (($data['status'] ?? 0) !== 1 || empty($data['product']['product_name'])) {
            return null;
        }

        $categories = $data['product']['categories'] ?? '';
        $premiereCategorie = trim(explode(',', (string) $categories)[0]);

        return new ProduitExterneDto(
            nom: (string) $data['product']['product_name'],
            categorieSuggeree: '' !== $premiereCategorie ? $premiereCategorie : null,
        );
    }
}
