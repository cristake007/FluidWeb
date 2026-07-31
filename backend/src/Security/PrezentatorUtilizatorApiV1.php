<?php

namespace App\Security;

use App\Entity\Utilizator;

final class PrezentatorUtilizatorApiV1
{
    /**
     * @return array{id: int, email: string, prenume: string, nume: string, roluri: list<string>, activ: bool}
     */
    public function construiesteDateUtilizator(Utilizator $utilizator): array
    {
        return [
            'id' => $utilizator->getId() ?? throw new \LogicException('Utilizatorul autentificat trebuie sa aiba ID.'),
            'email' => $utilizator->getEmail(),
            'prenume' => $utilizator->getPrenume(),
            'nume' => $utilizator->getNume(),
            'roluri' => $utilizator->getRoles(),
            'activ' => $utilizator->esteActiv(),
        ];
    }
}
