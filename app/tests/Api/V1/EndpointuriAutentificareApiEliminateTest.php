<?php

namespace App\Tests\Api\V1;

use Symfony\Component\HttpFoundation\Response;

final class EndpointuriAutentificareApiEliminateTest extends TestFunctionalApiV1
{
    public function testAutentificareaApiNuMaiExista(): void
    {
        $this->verificaRutaEliminata('POST', '/api/v1/autentificare');
    }

    public function testDeconectareaApiNuMaiExista(): void
    {
        $this->verificaRutaEliminata('POST', '/api/v1/deconectare');
    }

    public function testTokenulCsrfApiNuMaiExista(): void
    {
        $this->verificaRutaEliminata('GET', '/api/v1/token-csrf');
    }

    public function testUtilizatorulCurentApiNuMaiExista(): void
    {
        $this->verificaRutaEliminata('GET', '/api/v1/utilizator-curent');
    }

    private function verificaRutaEliminata(string $metoda, string $cale): void
    {
        $client = $this->creeazaClientCuLimitatorGol();
        $client->request($metoda, $cale);

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertJsonStringEqualsJsonString(
            '{"cod":404,"eroare":"resursa_negasita","mesaj":"Resursa solicitata nu a fost gasita."}',
            (string) $client->getResponse()->getContent(),
        );
    }
}
