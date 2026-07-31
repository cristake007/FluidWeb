<?php

namespace App\Tests;

use App\Entity\Utilizator;
use PHPUnit\Framework\TestCase;

final class UtilizatorTest extends TestCase
{
    public function testEmailIsNormalized(): void
    {
        $utilizator = (new Utilizator())->setEmail('  ANA.Exemplu@FluidWeb.RO  ');

        self::assertSame('ana.exemplu@fluidweb.ro', $utilizator->getEmail());
        self::assertSame('ana.exemplu@fluidweb.ro', $utilizator->getUserIdentifier());
    }

    public function testRolesAlwaysContainRoleUserWithoutDuplicates(): void
    {
        $utilizator = (new Utilizator())->setRoluri(['ROLE_ADMIN', 'ROLE_USER', 'ROLE_ADMIN']);

        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $utilizator->getRoles());
    }

    public function testUserIsActiveByDefault(): void
    {
        self::assertTrue((new Utilizator())->esteActiv());
    }

    public function testTimestampsAreInitializedInUtcAndUpdatedAutomatically(): void
    {
        $utilizator = new Utilizator();
        $creatLa = $utilizator->getCreatLa();
        $actualizatLa = $utilizator->getActualizatLa();

        usleep(1_000);
        $utilizator->updateActualizatLa();

        self::assertSame('UTC', $creatLa->getTimezone()->getName());
        self::assertSame('UTC', $actualizatLa->getTimezone()->getName());
        self::assertSame($creatLa, $actualizatLa);
        self::assertGreaterThan($actualizatLa, $utilizator->getActualizatLa());
    }
}
