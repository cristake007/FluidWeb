<?php

namespace App\Tests\Api\V1;

use App\Entity\Utilizator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class TestFunctionalApiV1 extends WebTestCase
{
    protected function creeazaClientCuLimitatorGol(): KernelBrowser
    {
        $client = static::createClient();
        $this->curataLimitatoare();

        return $client;
    }

    protected function tearDown(): void
    {
        $this->curataLimitatoare();

        parent::tearDown();
    }

    protected function autentificaClientPentruRutaWeb(KernelBrowser $client): void
    {
        $managerEntitati = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $managerEntitati);

        $utilizator = (new Utilizator())
            ->setEmail('test-web-'.bin2hex(random_bytes(8)).'@example.com')
            ->setParola('hash-nefolosit')
            ->setPrenume('Utilizator')
            ->setNume('Test');
        $managerEntitati->persist($utilizator);
        $managerEntitati->flush();

        $client->loginUser($utilizator);
    }

    private function curataLimitatoare(): void
    {
        foreach (['cache.api_v1_limiter', 'cache.autentificare_limiter'] as $idPoolCache) {
            $poolCache = self::getContainer()->get($idPoolCache);

            self::assertInstanceOf(CacheItemPoolInterface::class, $poolCache);
            $poolCache->clear();
        }
    }
}
