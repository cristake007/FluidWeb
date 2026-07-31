<?php

namespace App\Security;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener(event: LogoutEvent::class, priority: 65)]
final class AscultatorDeconectareApiV1
{
    public function __construct(
        private readonly CsrfTokenManagerInterface $managerTokenuriCsrf,
    ) {
    }

    public function __invoke(LogoutEvent $eveniment): void
    {
        if ('/api/v1/deconectare' !== $eveniment->getRequest()->getPathInfo()) {
            return;
        }

        $this->managerTokenuriCsrf->removeToken('api_v1');
        $eveniment->setResponse(new Response(status: Response::HTTP_NO_CONTENT));
    }
}
