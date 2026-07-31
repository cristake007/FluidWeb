<?php

namespace App\Tests\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

final class PaginaTestController
{
    public function __invoke(): Response
    {
        return new Response('Pagina de test');
    }

    public function scrieLog(LoggerInterface $logger): Response
    {
        $logger->info('log_non_api_test_corelare');

        return new Response();
    }
}
