<?php

namespace App\Tests\Controller\Api\V1;

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
}
