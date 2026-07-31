<?php

namespace App\Security;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
final class VerificatorCsrfApiV1
{
    private const ID_TOKEN = 'api_v1';

    public function __construct(
        private readonly CsrfTokenManagerInterface $managerTokenuriCsrf,
    ) {
    }

    public function __invoke(RequestEvent $eveniment): void
    {
        if (!$eveniment->isMainRequest()) {
            return;
        }

        $cerere = $eveniment->getRequest();

        if (!in_array($cerere->attributes->get('_route'), ['api_v1_autentificare', 'api_v1_deconectare'], true)) {
            return;
        }

        $this->valideazaCerere($cerere);
    }

    public function valideazaCerere(Request $cerere): void
    {
        $tokenCookie = $cerere->cookies->get('XSRF-TOKEN');
        $tokenHeader = $cerere->headers->get('X-XSRF-TOKEN');

        if (!is_string($tokenCookie) || !is_string($tokenHeader) || !hash_equals($tokenCookie, $tokenHeader)) {
            throw new AccessDeniedHttpException();
        }

        if (!$this->managerTokenuriCsrf->isTokenValid(new CsrfToken(self::ID_TOKEN, $tokenHeader))) {
            throw new AccessDeniedHttpException();
        }
    }
}
