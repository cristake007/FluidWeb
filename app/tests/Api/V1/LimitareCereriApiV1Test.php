<?php

namespace App\Tests\Api\V1;

use Symfony\Component\HttpFoundation\Response;

final class LimitareCereriApiV1Test extends TestFunctionalApiV1
{
    public function testPrimeleDouaCereriApiSuntAcceptateIarUrmatoareaEsteLimitata(): void
    {
        $client = $this->creeazaClientCuLimitatorGol();
        $server = ['REMOTE_ADDR' => '192.0.2.10'];

        $client->request('GET', '/api/v1/stare', server: $server);
        self::assertResponseIsSuccessful();

        $client->request('GET', '/api/v1/stare', server: $server);
        self::assertResponseIsSuccessful();

        $client->request('GET', '/api/v1/stare', server: $server);
        self::assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertJsonStringEqualsJsonString(
            '{"cod":429,"eroare":"prea_multe_cereri","mesaj":"Prea multe cereri. Incercati din nou mai tarziu."}',
            (string) $client->getResponse()->getContent(),
        );
    }

    public function testRutaNonApiNuEsteLimitata(): void
    {
        $client = $this->creeazaClientCuLimitatorGol();
        $this->autentificaClientPentruRutaWeb($client);
        $server = ['REMOTE_ADDR' => '192.0.2.11'];

        for ($cerere = 0; $cerere < 3; ++$cerere) {
            $client->request('GET', '/pagina-test', server: $server);

            self::assertResponseIsSuccessful();
        }
    }

    public function testRutaApiInexistentaRamane404SiNuConsumaLimita(): void
    {
        $client = $this->creeazaClientCuLimitatorGol();
        $server = ['REMOTE_ADDR' => '192.0.2.12'];

        $client->request('GET', '/api/v1/inexistenta', server: $server);
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $client->request('GET', '/api/v1/stare', server: $server);
        self::assertResponseIsSuccessful();

        $client->request('GET', '/api/v1/stare', server: $server);
        self::assertResponseIsSuccessful();

        $client->request('GET', '/api/v1/stare', server: $server);
        self::assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
    }
}
