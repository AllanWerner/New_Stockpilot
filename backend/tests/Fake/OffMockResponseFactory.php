<?php

declare(strict_types=1);

namespace App\Tests\Fake;

use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * Test-only stand-in for the real Open Food Facts API (config/services_test.yaml
 * swaps HttpClientInterface for a MockHttpClient using this factory), so functional
 * tests never hit the real network — see plan section J / CI job "backend-test".
 */
final class OffMockResponseFactory
{
    public const string CODE_BARRE_CONNU = '3017620422003';

    public function __invoke(string $method, string $url, array $options = []): MockResponse
    {
        if (str_contains($url, self::CODE_BARRE_CONNU.'.json')) {
            return new MockResponse(json_encode([
                'status' => 1,
                'product' => [
                    'product_name' => 'Pâte à tartiner Test',
                    'categories' => 'Pâtes à tartiner,Épicerie',
                ],
            ], JSON_THROW_ON_ERROR), ['http_code' => 200]);
        }

        return new MockResponse(json_encode(['status' => 0], JSON_THROW_ON_ERROR), ['http_code' => 404]);
    }
}
