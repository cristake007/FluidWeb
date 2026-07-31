<?php

namespace App\Tests\Controller\Api\V1;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

final class ControllerExceptiiFunctionale
{
    public function exceptieInterna(): never
    {
        throw new \RuntimeException('Mesaj intern care nu trebuie expus.');
    }

    public function preaMulteCereri(): never
    {
        throw new TooManyRequestsHttpException(60);
    }

    public function resursaNegasita(): never
    {
        throw new NotFoundHttpException();
    }

    public function scrieLog(LoggerInterface $logger): Response
    {
        $logger->info('log_api_test_corelare');

        return new Response();
    }
}
