<?php

namespace App\EvenimentListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 20)]
final class AscultatorLimitareCereriApiV1
{
    public function __construct(
        private readonly RateLimiterFactoryInterface $fabricaLimitatorApiV1,
    ) {
    }

    public function __invoke(RequestEvent $eveniment): void
    {
        if (!$eveniment->isMainRequest()) {
            return;
        }

        $cerere = $eveniment->getRequest();

        if (!str_starts_with($cerere->getPathInfo(), '/api/v1/') || !$cerere->attributes->has('_route')) {
            return;
        }

        // Autentificarea are propriul limitator Symfony, cu cheie pe utilizator si IP.
        if ('api_v1_autentificare' === $cerere->attributes->get('_route')) {
            return;
        }

        $cheie = $cerere->getClientIp() ?? 'ip_necunoscut';
        $rezultat = $this->fabricaLimitatorApiV1->create($cheie)->consume();

        if (!$rezultat->isAccepted()) {
            throw new HttpException(429);
        }
    }
}
