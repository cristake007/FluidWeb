<?php

namespace App\Controller\Api\V1;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AutentificareController
{
    #[Route('/api/v1/autentificare', name: 'api_v1_autentificare', methods: ['POST'], format: 'json')]
    public function autentificare(): Response
    {
        throw new \LogicException('Authenticatorul Symfony json_login trebuie sa intercepteze aceasta ruta.');
    }
}
