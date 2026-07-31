<?php

namespace App\Tests\Administrare;

use App\Entity\Utilizator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class AdministrareTest extends WebTestCase
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

    public function testAccesulDirectFaraRolAdminEsteInterzis(): void
    {
        $utilizator = $this->creeazaUtilizator('utilizator@example.com', ['ROLE_USER']);
        $this->client->loginUser($utilizator);

        $this->client->request('GET', '/administrare');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testPaginaAdministrareContineLegaturaCatreUtilizatori(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $this->client->loginUser($administrator);

        $pagina = $this->client->request('GET', '/administrare');

        self::assertResponseIsSuccessful();
        self::assertRouteSame('administrare');
        self::assertSelectorTextSame('h1', 'Administrare');
        self::assertCount(1, $pagina->filter('a.card[href="/administrare/utilizatori"]'));
        self::assertSelectorTextContains('.card', 'Utilizatori');
        self::assertCount(1, $pagina->filter('a.card[href="/administrare/branding"]'));
        self::assertSelectorTextContains('a.card[href="/administrare/branding"]', 'Branding');
    }

    public function testAdministratorulVedeAdministrareInainteDeDeconectare(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $this->client->loginUser($administrator);

        $pagina = $this->client->request('GET', '/');
        $dropdown = $pagina->filter('.header-aplicatie__actiuni .dropdown-menu');

        self::assertCount(1, $dropdown->filter('a.dropdown-item[href="/administrare"]'));
        self::assertSelectorTextSame('a.dropdown-item[href="/administrare"]', 'Administrare');
        self::assertCount(1, $dropdown->filter('a[href="/administrare"] .dropdown-item-icon'));
        self::assertCount(1, $dropdown->filter('a[href="/administrare"] + form[action="/deconectare"][method="post"]'));
        self::assertCount(1, $dropdown->filter('form[action="/deconectare"][method="post"] input[name="_csrf_token"][value]:not([value=""])'));
        self::assertCount(1, $dropdown->filter('form[action="/deconectare"] button[type="submit"]'));
    }

    public function testUtilizatorulObisnuitNuVedeAdministrareInDropdown(): void
    {
        $utilizator = $this->creeazaUtilizator('utilizator@example.com', ['ROLE_USER']);
        $this->client->loginUser($utilizator);

        $pagina = $this->client->request('GET', '/');
        $dropdown = $pagina->filter('.header-aplicatie__actiuni .dropdown-menu');

        self::assertCount(0, $dropdown->filter('a[href="/administrare"]'));
        self::assertCount(1, $dropdown->filter('form[action="/deconectare"][method="post"]'));
    }

    /** @param list<string> $roluri */
    private function creeazaUtilizator(string $email, array $roluri): Utilizator
    {
        $utilizator = (new Utilizator())
            ->setEmail($email)
            ->setPrenume('Utilizator')
            ->setNume('Test')
            ->setRoluri($roluri)
            ->setParola('hash-test');

        $this->managerEntitati->persist($utilizator);
        $this->managerEntitati->flush();

        return $utilizator;
    }
}
