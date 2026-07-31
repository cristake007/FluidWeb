<?php

namespace App\Tests\Administrare;

use App\Entity\Utilizator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class UtilizatoriTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $managerEntitati;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->managerEntitati = static::getContainer()->get(EntityManagerInterface::class);
        $this->managerEntitati->createQuery('DELETE FROM App\\Entity\\Utilizator')->execute();
        $this->managerEntitati->clear();
    }

    public function testAdministratorulVedePaginaSiUtilizatorii(): void
    {
        $administrator = $this->creeazaUtilizator(
            'admin@example.com',
            'Ana',
            'Administrator',
            ['ROLE_ADMIN'],
        );
        $this->creeazaUtilizator(
            'utilizator@example.com',
            'Mihai',
            'Ionescu',
            ['ROLE_USER'],
            false,
        );
        $this->client->loginUser($administrator);

        $pagina = $this->client->request('GET', '/administrare/utilizatori');

        self::assertResponseIsSuccessful();
        self::assertRouteSame('administrare_utilizatori');
        self::assertSelectorTextSame('h1', 'Utilizatori');
        self::assertSelectorTextContains('table', 'Ana Administrator');
        self::assertSelectorTextContains('table', 'admin@example.com');
        self::assertSelectorTextContains('table', 'Mihai Ionescu');
        self::assertSelectorTextContains('table', 'utilizator@example.com');
        self::assertSelectorTextContains('table', 'ROLE_ADMIN');
        self::assertSelectorTextContains('table', 'ROLE_USER');
        self::assertSelectorTextContains('table', 'Activ');
        self::assertSelectorTextContains('table', 'Inactiv');
        self::assertCount(6, $pagina->filter('table thead th'));
        self::assertGreaterThanOrEqual(4, $pagina->filter('table .badge')->count());
    }

    public function testUtilizatorulObisnuitPrimesteInterzis(): void
    {
        $utilizator = $this->creeazaUtilizator(
            'utilizator@example.com',
            'Mihai',
            'Ionescu',
            ['ROLE_USER'],
        );
        $this->client->loginUser($utilizator);

        $this->client->request('GET', '/administrare/utilizatori');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUtilizatorulAnonimEsteTrimisLaAutentificare(): void
    {
        $this->client->request('GET', '/administrare/utilizatori');

        self::assertResponseRedirects('/autentificare', Response::HTTP_FOUND);
    }

    public function testUtilizatoriiNuMaiAparInSidebar(): void
    {
        $administrator = $this->creeazaUtilizator(
            'admin@example.com',
            'Ana',
            'Administrator',
            ['ROLE_ADMIN'],
        );

        $this->client->loginUser($administrator);
        $paginaAdministratorului = $this->client->request('GET', '/');

        self::assertCount(1, $paginaAdministratorului->filter('.bara-laterala__navigatie .nav-item'));
        self::assertCount(0, $paginaAdministratorului->filter('.bara-laterala__navigatie a[href="/administrare/utilizatori"]'));
        self::assertStringNotContainsString('Utilizatori', $paginaAdministratorului->filter('.bara-laterala__navigatie')->text());
    }

    /** @param list<string> $roluri */
    private function creeazaUtilizator(
        string $email,
        string $prenume,
        string $nume,
        array $roluri,
        bool $activ = true,
    ): Utilizator {
        $utilizator = (new Utilizator())
            ->setEmail($email)
            ->setPrenume($prenume)
            ->setNume($nume)
            ->setRoluri($roluri)
            ->setActiv($activ)
            ->setParola('hash-test');

        $this->managerEntitati->persist($utilizator);
        $this->managerEntitati->flush();

        return $utilizator;
    }
}
