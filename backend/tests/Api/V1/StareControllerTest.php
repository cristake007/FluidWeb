<?php

namespace App\Tests\Api\V1;

final class StareControllerTest extends TestFunctionalApiV1
{
    public function testEndpointulDeStareRaspundeCorect(): void
    {
        $client = $this->creeazaClientCuLimitatorGol();

        $client->request('GET', '/api/v1/stare');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertJsonStringEqualsJsonString(
            '{"stare":"functional"}',
            (string) $client->getResponse()->getContent(),
        );
    }
}
