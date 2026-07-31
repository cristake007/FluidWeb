<?php

namespace App\Interfata;

final class ServiciuNavigatie
{
    /**
     * @return list<array{eticheta: string, ruta: string, iconita: string}>
     */
    public function obtineIntrari(): array
    {
        return [
            [
                'eticheta' => 'Panou de control',
                'ruta' => 'pagina_principala',
                'iconita' => 'dashboard',
            ],
        ];
    }
}
