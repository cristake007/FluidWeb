<?php

namespace App\EvenimentListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 21)]
final class AscultatorGenerareIdCorelareApiV1
{
    public const ATRIBUT_ID_CORELARE = 'id_corelare';

    public function __invoke(RequestEvent $eveniment): void
    {
        if (!$eveniment->isMainRequest()) {
            return;
        }

        $cerere = $eveniment->getRequest();

        if (!str_starts_with($cerere->getPathInfo(), '/api/v1/') || !$cerere->attributes->has('_route')) {
            return;
        }

        $cerere->attributes->set(self::ATRIBUT_ID_CORELARE, $this->genereazaIdCorelare());
    }

    private function genereazaIdCorelare(): string
    {
        $octeti = random_bytes(16);
        $octeti[6] = chr((ord($octeti[6]) & 0x0F) | 0x40);
        $octeti[8] = chr((ord($octeti[8]) & 0x3F) | 0x80);
        $sirHexazecimal = bin2hex($octeti);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($sirHexazecimal, 0, 8),
            substr($sirHexazecimal, 8, 4),
            substr($sirHexazecimal, 12, 4),
            substr($sirHexazecimal, 16, 4),
            substr($sirHexazecimal, 20),
        );
    }
}
