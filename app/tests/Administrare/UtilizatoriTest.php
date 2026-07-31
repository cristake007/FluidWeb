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
        self::assertCount(1, $pagina->filter('a.btn.btn-primary.btn-sm[href="/administrare/utilizatori/nou"]'));
        self::assertCount(1, $pagina->filter('a[href="/administrare/utilizatori/nou"] .ti.ti-user-plus.me-1'));
        self::assertSelectorTextSame('a.btn[href="/administrare/utilizatori/nou"]', 'Utilizator nou');
        self::assertSelectorTextContains('table', 'Ana Administrator');
        self::assertSelectorTextContains('table', 'admin@example.com');
        self::assertSelectorTextContains('table', 'Mihai Ionescu');
        self::assertSelectorTextContains('table', 'utilizator@example.com');
        self::assertSelectorTextContains('table', 'ROLE_ADMIN');
        self::assertSelectorTextContains('table', 'ROLE_USER');
        self::assertSelectorTextContains('table', 'Activ');
        self::assertSelectorTextContains('table', 'Inactiv');
        self::assertCount(7, $pagina->filter('table thead th'));
        self::assertSelectorTextContains('table thead', 'Acțiuni');
        self::assertCount(2, $pagina->filter('table tbody a.btn.btn-ghost-primary.btn-sm.px-2[title="Editează"] .ti.ti-edit'));
        self::assertCount(2, $pagina->filter('table tbody button[data-bs-toggle="modal"][title="Resetează parola"] .ti.ti-key'));
        self::assertCount(2, $pagina->filter('.modal form.modal-content[method="post"]:not([data-turbo="false"]) button[type="submit"] .ti.ti-key'));
        self::assertCount(2, $pagina->filter('.modal form input[name="_csrf_token"][value]:not([value=""])'));
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
