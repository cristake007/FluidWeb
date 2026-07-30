<?php

namespace App\Tests\Api\V1;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class StareControllerTest extends WebTestCase
{
    public function testEndpointulDeStareRaspundeCorect(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/v1/stare');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertJsonStringEqualsJsonString(
            '{"stare":"functional"}',
            (string) $client->getResponse()->getContent(),
        );
    }
}
