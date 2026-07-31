<?php

namespace App\EvenimentListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE)]
final class AscultatorRaspunsIdCorelareApiV1
{
    public function __invoke(ResponseEvent $eveniment): void
    {
        if (!$eveniment->isMainRequest()) {
            return;
        }

        $idCorelare = $eveniment->getRequest()->attributes->get(AscultatorGenerareIdCorelareApiV1::ATRIBUT_ID_CORELARE);

        if (!is_string($idCorelare)) {
            return;
        }

        $eveniment->getResponse()->headers->set('X-Correlation-ID', $idCorelare);
    }
}
