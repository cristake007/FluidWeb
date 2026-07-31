<?php

namespace App\Tests\Api\V1;

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

    private function curataLimitatoare(): void
    {
        foreach (['cache.api_v1_limiter', 'cache.autentificare_limiter'] as $idPoolCache) {
            $poolCache = self::getContainer()->get($idPoolCache);

            self::assertInstanceOf(CacheItemPoolInterface::class, $poolCache);
            $poolCache->clear();
        }
    }
}
