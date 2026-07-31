<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class PunctIntrareApiV1 implements AuthenticationEntryPointInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse([
            'cod' => Response::HTTP_UNAUTHORIZED,
            'eroare' => 'autentificare_necesara',
            'mesaj' => 'Autentificarea este necesara pentru accesarea acestei resurse.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}
