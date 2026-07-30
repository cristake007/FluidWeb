<?php

namespace App\Controller\Api\V1;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class StareController
{
    #[Route(
        '/api/v1/stare',
        name: 'api_v1_stare',
        methods: ['GET'],
        format: 'json',
    )]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse([
            'stare' => 'functional',
        ]);
    }
}
