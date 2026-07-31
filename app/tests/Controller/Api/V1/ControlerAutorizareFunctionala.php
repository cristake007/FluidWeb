<?php

namespace App\Tests\Controller\Api\V1;

use App\Security\Permisiuni;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ControlerAutorizareFunctionala
{
    #[IsGranted(Permisiuni::UTILIZATORI_VIZUALIZEAZA)]
    public function verificaAccesul(): JsonResponse
    {
        return new JsonResponse(['acces' => 'permis']);
    }
}
