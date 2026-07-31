<?php

namespace App\ProcesatorLog;

use App\EvenimentListener\AscultatorGenerareIdCorelareApiV1;
use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsMonologProcessor]
final class ProcesatorIdCorelareApiV1
{
    public function __construct(
        private readonly RequestStack $stivaCereri,
    ) {
    }

    public function __invoke(LogRecord $inregistrare): LogRecord
    {
        $idCorelare = $this->stivaCereri->getCurrentRequest()?->attributes->get(AscultatorGenerareIdCorelareApiV1::ATRIBUT_ID_CORELARE);

        if (!is_string($idCorelare)) {
            return $inregistrare;
        }

        return $inregistrare->with(extra: [
            ...$inregistrare->extra,
            'id_corelare' => $idCorelare,
        ]);
    }
}
