<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;

final class PaginaTestController
{
    public function __invoke(): Response
    {
        return new Response('Pagina de test');
    }
}
