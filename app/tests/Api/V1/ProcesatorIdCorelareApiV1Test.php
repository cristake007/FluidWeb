<?php

namespace App\Tests\Api\V1;

use Monolog\Handler\TestHandler;
use Monolog\LogRecord;

final class ProcesatorIdCorelareApiV1Test extends TestFunctionalApiV1
{
    public function testLogulApiContineIdCorelareDinHeaderulRaspunsului(): void
    {
        $client = $this->creeazaClientCuLimitatorGol();
        $handler = $this->obtineHandlerTest();
        $handler->clear();

        $client->request('GET', '/api/v1/test/log-corelare');

        self::assertResponseIsSuccessful();
        $idCorelare = $client->getResponse()->headers->get('X-Correlation-ID');
        self::assertIsString($idCorelare);

        $inregistrare = $this->obtineInregistrare($handler, 'log_api_test_corelare');
        self::assertSame($idCorelare, $inregistrare->extra['id_corelare'] ?? null);
    }

    public function testLogulNonApiNuContineIdCorelare(): void
    {
        $client = $this->creeazaClientCuLimitatorGol();
        $this->autentificaClientPentruRutaWeb($client);
        $handler = $this->obtineHandlerTest();
        $handler->clear();

        $client->request('GET', '/pagina-test/log-corelare');

        self::assertResponseIsSuccessful();
        $inregistrare = $this->obtineInregistrare($handler, 'log_non_api_test_corelare');
        self::assertArrayNotHasKey('id_corelare', $inregistrare->extra);
    }

    private function obtineHandlerTest(): TestHandler
    {
        $handler = self::getContainer()->get('monolog.handler.test');

        self::assertInstanceOf(TestHandler::class, $handler);

        return $handler;
    }

    private function obtineInregistrare(TestHandler $handler, string $mesaj): LogRecord
    {
        foreach ($handler->getRecords() as $inregistrare) {
            if ($mesaj === $inregistrare->message) {
                return $inregistrare;
            }
        }

        self::fail(sprintf('Nu a fost gasita inregistrarea de log "%s".', $mesaj));
    }
}
