<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class PunctIntrareAplicatie implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $generatorUrl,
    ) {
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        if (str_starts_with($request->getPathInfo(), '/api/v1')) {
            return new JsonResponse([
                'cod' => Response::HTTP_UNAUTHORIZED,
                'eroare' => 'autentificare_necesara',
                'mesaj' => 'Autentificarea este necesara pentru accesarea acestei resurse.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return new RedirectResponse($this->generatorUrl->generate('autentificare'));
    }
}
