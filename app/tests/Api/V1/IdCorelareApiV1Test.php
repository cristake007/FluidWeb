<?php

namespace App\Tests\Api\V1;

use Symfony\Component\HttpFoundation\Response;

final class IdCorelareApiV1Test extends TestFunctionalApiV1
{
    public function testRaspunsulApiReusitAreIdCorelareUuid(): void
    {
        $client = $this->creeazaClientCuLimitatorGol();

        $client->request('GET', '/api/v1/stare');

        self::assertResponseIsSuccessful();
        self::assertTrue($client->getResponse()->headers->has('X-Correlation-ID'));
        self::assertEsteUuid((string) $client->getResponse()->headers->get('X-Correlation-ID'));
    }

    public function testCereriDiferitePrimescIduriCorelareDiferite(): void
    {
        $client = $this->creeazaClientCuLimitatorGol();

        $client->request('GET', '/api/v1/stare');
        $primulIdCorelare = $client->getResponse()->headers->get('X-Correlation-ID');

        $client->request('GET', '/api/v1/stare');
        $alDoileaIdCorelare = $client->getResponse()->headers->get('X-Correlation-ID');

        self::assertIsString($primulIdCorelare);
        self::assertIsString($alDoileaIdCorelare);
        self::assertNotSame($primulIdCorelare, $alDoileaIdCorelare);
    }

    public function testErorileApi404Si500AuIdCorelare(): void
    {
        $client = $this->creeazaClientCuLimitatorGol();

        $client->request('GET', '/api/v1/test/resursa-negasita');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertEsteUuid((string) $client->getResponse()->headers->get('X-Correlation-ID'));

        $client->request('GET', '/api/v1/test/exceptie-interna');
        self::assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);
        self::assertEsteUuid((string) $client->getResponse()->headers->get('X-Correlation-ID'));
    }

    public function testRaspunsul429AreIdCorelare(): void
    {
        $client = $this->creeazaClientCuLimitatorGol();

        $client->request('GET', '/api/v1/stare');
        $client->request('GET', '/api/v1/stare');
        $client->request('GET', '/api/v1/stare');

        self::assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
        self::assertEsteUuid((string) $client->getResponse()->headers->get('X-Correlation-ID'));
    }

    public function testPaginaNonApiNuAreIdCorelare(): void
    {
        $client = $this->creeazaClientCuLimitatorGol();

        $client->request('GET', '/pagina-test');

        self::assertResponseIsSuccessful();
        self::assertFalse($client->getResponse()->headers->has('X-Correlation-ID'));
    }

    public function testIdCorelareTrimisDeClientEsteInlocuit(): void
    {
        $client = $this->creeazaClientCuLimitatorGol();
        $idCorelareClient = 'id-controlat-de-client';

        $client->request('GET', '/api/v1/stare', server: ['HTTP_X_CORRELATION_ID' => $idCorelareClient]);

        self::assertResponseIsSuccessful();
        $idCorelareRaspuns = $client->getResponse()->headers->get('X-Correlation-ID');
        self::assertIsString($idCorelareRaspuns);
        self::assertNotSame($idCorelareClient, $idCorelareRaspuns);
        self::assertEsteUuid($idCorelareRaspuns);
    }

    private static function assertEsteUuid(string $valoare): void
    {
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $valoare,
        );
    }
}
