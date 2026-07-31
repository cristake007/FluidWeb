<?php

namespace App\EvenimentListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
final class AscultatorExceptiiApiV1
{
    /**
     * @var array<int, array{eroare: string, mesaj: string}>
     */
    private const ERORI = [
        400 => ['eroare' => 'cerere_invalida', 'mesaj' => 'Cererea trimisa nu este valida.'],
        401 => ['eroare' => 'autentificare_necesara', 'mesaj' => 'Autentificarea este necesara pentru accesarea acestei resurse.'],
        403 => ['eroare' => 'acces_interzis', 'mesaj' => 'Nu aveti permisiunea necesara pentru accesarea acestei resurse.'],
        404 => ['eroare' => 'resursa_negasita', 'mesaj' => 'Resursa solicitata nu a fost gasita.'],
        405 => ['eroare' => 'metoda_nepermisa', 'mesaj' => 'Metoda HTTP utilizata nu este permisa pentru aceasta resursa.'],
        409 => ['eroare' => 'conflict', 'mesaj' => 'Cererea nu poate fi finalizata din cauza unui conflict.'],
        422 => ['eroare' => 'date_invalide', 'mesaj' => 'Datele trimise nu au putut fi procesate.'],
        429 => ['eroare' => 'prea_multe_cereri', 'mesaj' => 'Prea multe cereri. Incercati din nou mai tarziu.'],
        500 => ['eroare' => 'eroare_interna', 'mesaj' => 'A aparut o eroare interna.'],
        503 => ['eroare' => 'serviciu_indisponibil', 'mesaj' => 'Serviciul este temporar indisponibil.'],
    ];

    public function __invoke(ExceptionEvent $eveniment): void
    {
        if (!str_starts_with($eveniment->getRequest()->getPathInfo(), '/api/v1')) {
            return;
        }

        $exceptie = $eveniment->getThrowable();
        $cod = $exceptie instanceof HttpExceptionInterface ? $exceptie->getStatusCode() : 500;

        if (in_array($cod, [301, 302, 307, 308], true)) {
            return;
        }

        $detalii = self::ERORI[$cod] ?? [
            'eroare' => 'eroare_http',
            'mesaj' => 'Cererea nu a putut fi procesata.',
        ];

        $headere = $exceptie instanceof HttpExceptionInterface ? $exceptie->getHeaders() : [];

        $eveniment->setResponse(new JsonResponse([
            'cod' => $cod,
            'eroare' => $detalii['eroare'],
            'mesaj' => $detalii['mesaj'],
        ], $cod, $headere));
    }
}
