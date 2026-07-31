<?php

namespace App\Tests\Api\V1;

use Monolog\Handler\TestHandler;
use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\Response;

final class JurnalCereriTehniceApiV1Test extends TestFunctionalApiV1
{
    public function testCerereaApiProduceUnSingurJurnalTehnicFaraDateSensibile(): void
    {
        $client = $this->creeazaClientCuLimitatorGol();
        $handler = $this->obtineHandlerTest();
        $handler->clear();

        $client->request('GET', '/api/v1/stare?token=secret', server: [
            'HTTP_AUTHORIZATION' => 'Bearer secret',
            'HTTP_COOKIE' => 'sesiune=secret',
        ]);

        self::assertResponseIsSuccessful();
        $inregistrari = $this->obtineJurnaleTehnice($handler);
        self::assertCount(1, $inregistrari);

        $inregistrare = $inregistrari[0];
        self::assertSame($client->getResponse()->headers->get('X-Correlation-ID'), $inregistrare->extra['id_corelare'] ?? null);
        self::assertSame('GET', $inregistrare->context['metoda_http'] ?? null);
        self::assertSame('api_v1_stare', $inregistrare->context['ruta'] ?? null);
        self::assertSame(Response::HTTP_OK, $inregistrare->context['status_http'] ?? null);
        self::assertIsNumeric($inregistrare->context['durata_milisecunde'] ?? null);
        self::assertGreaterThanOrEqual(0, $inregistrare->context['durata_milisecunde']);
        self::assertArrayHasKey('ip_client', $inregistrare->context);
        self::assertSame(
            ['metoda_http', 'ruta', 'status_http', 'durata_milisecunde', 'ip_client'],
            array_keys($inregistrare->context),
        );
    }

    public function testRaspunsul429EsteJurnalizat(): void
    {
        $client = $this->creeazaClientCuLimitatorGol();
        $client->disableReboot();
        $handler = $this->obtineHandlerTest();
        $handler->clear();

        $client->request('GET', '/api/v1/stare');
        $client->request('GET', '/api/v1/stare');
        $client->request('GET', '/api/v1/stare');
        self::assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);

        $jurnale429 = $this->obtineJurnaleTehnice($handler);
        self::assertCount(1, $jurnale429);
        self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, $jurnale429[0]->context['status_http'] ?? null);
    }

    public function testRaspunsul500EsteJurnalizat(): void
    {
        $client = $this->creeazaClientCuLimitatorGol();
        $handler = $this->obtineHandlerTest();
        $handler->clear();

        $client->request('GET', '/api/v1/test/exceptie-interna');
        self::assertResponseStatusCodeSame(Response::HTTP_INTERNAL_SERVER_ERROR);

        $jurnale500 = $this->obtineJurnaleTehnice($handler);
        self::assertCount(1, $jurnale500);
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $jurnale500[0]->context['status_http'] ?? null);
    }

    public function testPaginaNonApiNuProduceJurnalTehnic(): void
    {
        $client = $this->creeazaClientCuLimitatorGol();
        $this->autentificaClientPentruRutaWeb($client);
        $handler = $this->obtineHandlerTest();
        $handler->clear();

        $client->request('GET', '/pagina-test');

        self::assertResponseIsSuccessful();
        self::assertSame([], $this->obtineJurnaleTehnice($handler));
    }

    private function obtineHandlerTest(): TestHandler
    {
        $handler = self::getContainer()->get('monolog.handler.test');

        self::assertInstanceOf(TestHandler::class, $handler);

        return $handler;
    }

    /**
     * @return list<LogRecord>
     */
    private function obtineJurnaleTehnice(TestHandler $handler): array
    {
        return array_values(array_filter(
            $handler->getRecords(),
            static fn (LogRecord $inregistrare): bool => 'cerere_tehnica_api' === $inregistrare->message,
        ));
    }
}
