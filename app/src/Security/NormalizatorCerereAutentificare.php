<?php

namespace App\Security;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 11)]
final class NormalizatorCerereAutentificare
{
    public function __invoke(RequestEvent $eveniment): void
    {
        if (!$eveniment->isMainRequest()) {
            return;
        }

        $cerere = $eveniment->getRequest();
        if ('api_v1_autentificare' !== $cerere->attributes->get('_route')) {
            return;
        }

        try {
            $date = json_decode($cerere->getContent(), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return;
        }

        if (!is_array($date) || !isset($date['email']) || !is_string($date['email'])) {
            return;
        }

        $date['email'] = mb_strtolower(trim($date['email']));
        $cerere->initialize(
            $cerere->query->all(),
            [],
            $cerere->attributes->all(),
            $cerere->cookies->all(),
            $cerere->files->all(),
            $cerere->server->all(),
            json_encode($date, JSON_THROW_ON_ERROR),
        );
    }
}
