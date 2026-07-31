<?php

namespace App\Tests\Interfata;

use App\Interfata\ServiciuNavigatie;
use PHPUnit\Framework\TestCase;

final class ServiciuNavigatieTest extends TestCase
{
    public function testFurnizeazaNavigatiaAplicatiei(): void
    {
        $serviciu = new ServiciuNavigatie();

        self::assertSame([
            [
                'eticheta' => 'Panou de control',
                'ruta' => 'pagina_principala',
                'iconita' => 'dashboard',
            ],
        ], $serviciu->obtineIntrari());
    }
}
