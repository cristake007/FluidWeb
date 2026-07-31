<?php

namespace App\Controller\Api\V1;

use App\Entity\Utilizator;
use App\Security\PrezentatorUtilizatorApiV1;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class UtilizatorCurentController extends AbstractController
{
    public function __construct(
        private readonly PrezentatorUtilizatorApiV1 $prezentatorUtilizator,
    ) {
    }

    #[Route('/api/v1/utilizator-curent', name: 'api_v1_utilizator_curent', methods: ['GET'], format: 'json')]
    public function utilizatorCurent(): JsonResponse
    {
        $utilizator = $this->getUser();

        if (!$utilizator instanceof Utilizator) {
            throw $this->createAccessDeniedException();
        }

        return new JsonResponse($this->prezentatorUtilizator->construiesteDateUtilizator($utilizator));
    }
}
