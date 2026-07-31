<?php

namespace App\Controller\Api\V1;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeconectareController
{
    #[Route('/api/v1/deconectare', name: 'api_v1_deconectare', methods: ['POST'], format: 'json')]
    public function deconectare(): Response
    {
        throw new \LogicException('Listenerul Symfony de deconectare trebuie sa intercepteze aceasta ruta.');
    }
}
