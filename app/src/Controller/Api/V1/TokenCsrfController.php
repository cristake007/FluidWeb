<?php

namespace App\Controller\Api\V1;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class TokenCsrfController
{
    public function __construct(
        private readonly CsrfTokenManagerInterface $managerTokenuriCsrf,
    ) {
    }

    #[Route('/api/v1/token-csrf', name: 'api_v1_token_csrf', methods: ['GET'], format: 'json')]
    public function tokenCsrf(): Response
    {
        $token = $this->managerTokenuriCsrf->getToken('api_v1')->getValue();
        $raspuns = new Response(status: Response::HTTP_NO_CONTENT);
        $raspuns->headers->setCookie(Cookie::create(
            'XSRF-TOKEN',
            $token,
            path: '/',
            secure: null,
            httpOnly: false,
            sameSite: Cookie::SAMESITE_LAX,
        ));

        return $raspuns;
    }
}
