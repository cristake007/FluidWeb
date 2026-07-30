<?php

namespace App\Tests\Api\V1;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EroriApiV1Test extends WebTestCase
{
    public function testRutaApiInexistentaRespectaContractul(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/inexistenta');

        self::assertResponseStatusCodeSame(404);
        self::assertRaspunsEroare([
            'cod' => 404,
            'eroare' => 'resursa_negasita',
            'mesaj' => 'Resursa solicitata nu a fost gasita.',
        ], $client->getResponse()->getContent());
    }

    public function testMetodaNepermisaPastreazaHeaderulAllow(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/v1/stare');

        self::assertResponseStatusCodeSame(405);
        self::assertResponseHeaderSame('allow', 'GET');
        self::assertRaspunsEroare([
            'cod' => 405,
            'eroare' => 'metoda_nepermisa',
            'mesaj' => 'Metoda HTTP utilizata nu este permisa pentru aceasta resursa.',
        ], $client->getResponse()->getContent());
    }

    public function testExceptiaInternaNuExpuneMesajulOriginal(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/test/exceptie-interna');

        self::assertResponseStatusCodeSame(500);
        self::assertRaspunsEroare([
            'cod' => 500,
            'eroare' => 'eroare_interna',
            'mesaj' => 'A aparut o eroare interna.',
        ], $client->getResponse()->getContent());
        self::assertStringNotContainsString('Mesaj intern care nu trebuie expus.', (string) $client->getResponse()->getContent());
    }

    public function testRutaNonApiNuPrimesteContractulApi(): void
    {
        $client = static::createClient();

        $client->request('GET', '/ruta-inexistenta');

        self::assertResponseStatusCodeSame(404);
        self::assertNotSame('application/json', $client->getResponse()->headers->get('content-type'));
    }

    public function testHeaderulRetryAfterEstePastra(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/test/prea-multe-cereri');

        self::assertResponseStatusCodeSame(429);
        self::assertResponseHeaderSame('retry-after', '60');
        self::assertRaspunsEroare([
            'cod' => 429,
            'eroare' => 'prea_multe_cereri',
            'mesaj' => 'Au fost trimise prea multe cereri. Incercati din nou mai tarziu.',
        ], $client->getResponse()->getContent());
    }

    /**
     * @param array{cod: int, eroare: string, mesaj: string} $asteptat
     */
    private static function assertRaspunsEroare(array $asteptat, string|false $continut): void
    {
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertJsonStringEqualsJsonString(json_encode($asteptat, JSON_THROW_ON_ERROR), (string) $continut);
    }
}
