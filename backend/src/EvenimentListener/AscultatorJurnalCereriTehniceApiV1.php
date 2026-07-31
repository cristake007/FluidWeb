<?php

namespace App\EvenimentListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class AscultatorJurnalCereriTehniceApiV1
{
    private const ATRIBUT_INCEPUT_CERERE = 'inceput_cerere_tehnica_api';

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    #[AsEventListener(event: KernelEvents::REQUEST, priority: 22)]
    public function marcheazaInceputCerere(RequestEvent $eveniment): void
    {
        if (!$eveniment->isMainRequest()) {
            return;
        }

        $cerere = $eveniment->getRequest();

        if (!str_starts_with($cerere->getPathInfo(), '/api/v1/') || !$cerere->attributes->has('_route')) {
            return;
        }

        $cerere->attributes->set(self::ATRIBUT_INCEPUT_CERERE, microtime(true));
    }

    #[AsEventListener(event: KernelEvents::RESPONSE)]
    public function scrieJurnal(ResponseEvent $eveniment): void
    {
        if (!$eveniment->isMainRequest()) {
            return;
        }

        $cerere = $eveniment->getRequest();
        $inceputCerere = $cerere->attributes->get(self::ATRIBUT_INCEPUT_CERERE);
        $ruta = $cerere->attributes->get('_route');

        if (!is_float($inceputCerere) || !is_string($ruta)) {
            return;
        }

        $this->logger->info('cerere_tehnica_api', [
            'metoda_http' => $cerere->getMethod(),
            'ruta' => $ruta,
            'status_http' => $eveniment->getResponse()->getStatusCode(),
            'durata_milisecunde' => max(0, (microtime(true) - $inceputCerere) * 1000),
            'ip_client' => $cerere->getClientIp(),
        ]);
    }
}
