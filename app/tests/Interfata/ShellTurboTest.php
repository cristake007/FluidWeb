<?php

namespace App\Tests\Interfata;

use App\Entity\Utilizator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;

final class ShellTurboTest extends WebTestCase
{
    private const ID_CADRU = 'continut-aplicatie';

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

    public function testToatePaginileAutentificateLivreazaAcelasiCadruGlobal(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $this->client->loginUser($administrator);

        foreach ([
            '/' => 'Panou de control',
            '/administrare' => 'Administrare',
            '/administrare/utilizatori' => 'Utilizatori',
        ] as $url => $titlu) {
            $pagina = $this->cerereTurbo('GET', $url);

            self::assertResponseIsSuccessful();
            self::assertCount(1, $pagina->filter($this->selectorCadru()));
            self::assertSelectorTextSame($this->selectorCadru().' h1', $titlu);
            self::assertCount(1, $pagina->filter('[data-shell-aplicatie] > header[data-header-aplicatie]'));
            self::assertCount(1, $pagina->filter('[data-shell-aplicatie] > aside.bara-laterala'));
        }
    }

    public function testNavigatiaShelluluiTintesteCadrulFaraSaIlReconstruiasca(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $this->client->loginUser($administrator);

        $pagina = $this->client->request('GET', '/');

        self::assertCount(1, $pagina->filter($this->selectorCadru()));
        self::assertCount(1, $pagina->filter('header a.header-aplicatie__brand[data-turbo-frame="continut-aplicatie"]:not([data-turbo-action])'));
        self::assertCount(1, $pagina->filter('aside a[href="/"][data-turbo-frame="continut-aplicatie"]:not([data-turbo-action])'));
        self::assertCount(1, $pagina->filter('header a[href="/administrare"][data-turbo-frame="continut-aplicatie"]:not([data-turbo-action])'));

        foreach (['/', '/administrare', '/administrare/utilizatori'] as $url) {
            $pagina = $this->cerereTurbo('GET', $url);

            self::assertCount(1, $pagina->filter($this->selectorCadru()));
        }
    }

    public function testFormulareleDeCreareEditareSiResetareRamanInCadrulGlobal(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $utilizator = $this->creeazaUtilizator('utilizator@example.com', []);
        $idUtilizator = $utilizator->getId();
        self::assertNotNull($idUtilizator);
        $this->client->loginUser($administrator);

        $paginaCreare = $this->cerereTurbo('GET', '/administrare/utilizatori/nou');
        self::assertCount(1, $paginaCreare->filter($this->selectorCadru().' form[name="utilizator_nou"][data-turbo-action="advance"]:not([data-turbo="false"])'));

        $paginaEditare = $this->cerereTurbo('GET', sprintf('/administrare/utilizatori/%d/editeaza', $idUtilizator));
        self::assertCount(1, $paginaEditare->filter($this->selectorCadru().' form[name="editare_utilizator"][data-turbo-action="advance"]:not([data-turbo="false"])'));

        $paginaUtilizatori = $this->cerereTurbo('GET', '/administrare/utilizatori');
        $formularResetare = $paginaUtilizatori->filter(sprintf(
            '%s form[action="/administrare/utilizatori/%d/resetare-parola"]:not([data-turbo="false"])',
            $this->selectorCadru(),
            $idUtilizator,
        ));
        self::assertCount(1, $formularResetare);

        $paginaParola = $this->client->submit($formularResetare->form(), [], [
            'HTTP_TURBO_FRAME' => self::ID_CADRU,
        ]);
        self::assertResponseIsSuccessful();
        self::assertCount(1, $paginaParola->filter($this->selectorCadru().' [data-parola-generata]'));
        self::assertCount(1, $paginaParola->filter('meta[name="turbo-cache-control"][content="no-cache"]'));
    }

    public function testSesiuneaExpirataIeseDinCadruCatreAutentificare(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $this->client->loginUser($administrator);
        $this->cerereTurbo('GET', '/administrare');

        $this->client->restart();
        $this->cerereTurbo('GET', '/administrare/utilizatori');

        self::assertResponseRedirects('/autentificare', Response::HTTP_FOUND);
        $paginaAutentificare = $this->client->followRedirect();
        self::assertCount(1, $paginaAutentificare->filter('meta[name="turbo-visit-control"][content="reload"]'));
        self::assertCount(0, $paginaAutentificare->filter('turbo-frame'));
    }

    public function testAutentificareaSiDeconectareaFolosescNavigareCompleta(): void
    {
        $paginaAutentificare = $this->client->request('GET', '/autentificare');

        self::assertCount(1, $paginaAutentificare->filter('meta[name="turbo-visit-control"][content="reload"]'));
        self::assertCount(1, $paginaAutentificare->filter('form[action="/autentificare"][data-turbo="false"]'));

        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $this->client->loginUser($administrator);
        $paginaAplicatie = $this->client->request('GET', '/');
        $formularDeconectare = $paginaAplicatie->filter('form[action="/deconectare"][data-turbo="false"]');

        self::assertCount(1, $formularDeconectare);
        $this->client->submit($formularDeconectare->form());
        self::assertResponseRedirects('/autentificare');
    }

    private function cerereTurbo(string $metoda, string $url): Crawler
    {
        return $this->client->request($metoda, $url, server: [
            'HTTP_TURBO_FRAME' => self::ID_CADRU,
        ]);
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

    private function selectorCadru(): string
    {
        return 'main#continut-principal > turbo-frame#continut-aplicatie[data-turbo-action="advance"]';
    }
}
