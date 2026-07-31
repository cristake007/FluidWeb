<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final class EsecAutentificareHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        if ($exception instanceof TooManyLoginAttemptsAuthenticationException) {
            return new JsonResponse([
                'cod' => Response::HTTP_TOO_MANY_REQUESTS,
                'eroare' => 'prea_multe_cereri',
                'mesaj' => 'Prea multe cereri. Incercati din nou mai tarziu.',
            ], Response::HTTP_TOO_MANY_REQUESTS, $this->construiesteHeadereLimitare($exception));
        }

        return new JsonResponse([
            'cod' => Response::HTTP_UNAUTHORIZED,
            'eroare' => 'autentificare_esuata',
            'mesaj' => 'Datele de autentificare sunt invalide.',
        ], Response::HTTP_UNAUTHORIZED);
    }

    /** @return array<string, string> */
    private function construiesteHeadereLimitare(TooManyLoginAttemptsAuthenticationException $exception): array
    {
        $minute = $exception->getMessageData()['%minutes%'] ?? null;

        return is_int($minute) && $minute > 0 ? ['Retry-After' => (string) ($minute * 60)] : [];
    }
}
