<?php

namespace App\Security;

use App\Entity\Utilizator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final class SuccesAutentificareHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private readonly PrezentatorUtilizatorApiV1 $prezentatorUtilizator,
    ) {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $utilizator = $token->getUser();

        if (!$utilizator instanceof Utilizator) {
            throw new \LogicException('Tokenul de autentificare nu contine un utilizator FluidWeb.');
        }

        return new JsonResponse([
            'utilizator' => $this->prezentatorUtilizator->construiesteDateUtilizator($utilizator),
        ]);
    }
}
