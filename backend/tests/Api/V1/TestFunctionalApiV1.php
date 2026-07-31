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
        $poolCache = self::getContainer()->get('cache.api_v1_limiter');

        self::assertInstanceOf(CacheItemPoolInterface::class, $poolCache);
        $poolCache->clear();

        return $client;
    }

    protected function tearDown(): void
    {
        $poolCache = self::getContainer()->get('cache.api_v1_limiter');

        if ($poolCache instanceof CacheItemPoolInterface) {
            $poolCache->clear();
        }

        parent::tearDown();
    }
}
