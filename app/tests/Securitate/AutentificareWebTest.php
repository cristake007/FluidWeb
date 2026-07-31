<?php

namespace App\Tests\Securitate;

use App\Entity\Utilizator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AutentificareWebTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $managerEntitati;
    private UserPasswordHasherInterface $hasherParole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->managerEntitati = $container->get(EntityManagerInterface::class);
        $this->hasherParole = $container->get(UserPasswordHasherInterface::class);
        $this->managerEntitati->createQuery('DELETE FROM App\\Entity\\Utilizator')->execute();
        $this->managerEntitati->clear();
        $this->curataLimitatorulAutentificarii();
    }

    protected function tearDown(): void
    {
        $this->curataLimitatorulAutentificarii();

        parent::tearDown();
    }

    public function testPaginaPrincipalaRedirectioneazaUtilizatorulAnonimLaAutentificare(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseRedirects('/autentificare', Response::HTTP_FOUND);
        self::assertStringNotContainsString('data-shell-aplicatie', (string) $this->client->getResponse()->getContent());
    }

    public function testPaginaDeAutentificareRaspundeCuHtml(): void
    {
        $pagina = $this->client->request('GET', '/autentificare');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'text/html; charset=UTF-8');
        self::assertCount(1, $pagina->filter('meta[name="viewport"][content="width=device-width, initial-scale=1"]'));
        self::assertCount(1, $pagina->filter('body.layout-securitate'));
        self::assertCount(1, $pagina->filter('main.pagina-autentificare[data-layout="securitate"]'));
        self::assertCount(1, $pagina->filter('.pagina-autentificare__imagine img[src^="/build/images/"]'));
        self::assertCount(0, $pagina->filter('[data-mesaje-flash]'));
    }

    public function testFormularulContineCampurileNecesare(): void
    {
        $pagina = $this->client->request('GET', '/autentificare');

        self::assertCount(1, $pagina->filter('input[name="email"][autocomplete="username"]'));
        self::assertCount(1, $pagina->filter('input[name="parola"][autocomplete="current-password"]'));
        self::assertCount(1, $pagina->filter('input[name="_csrf_token"]'));
        self::assertCount(1, $pagina->filter('form[action="/autentificare"][method="post"][novalidate][data-turbo="false"][data-controller="validare-formular"]'));
        self::assertCount(2, $pagina->filter('input[required][data-validare-formular-target="camp"]'));
        self::assertCount(1, $pagina->filter('#eroare-email.invalid-feedback[aria-live="polite"]'));
        self::assertCount(1, $pagina->filter('#eroare-parola.invalid-feedback[aria-live="polite"]'));
        self::assertSelectorTextSame('#eroare-email', 'Introduceți adresa de email.');
        self::assertSelectorTextSame('#eroare-parola', 'Introduceți parola.');
    }

    public function testPaginaDeAutentificareNuContineShellSauResurseExterne(): void
    {
        $pagina = $this->client->request('GET', '/autentificare');

        self::assertCount(0, $pagina->filter('.navbar, .sidebar, [data-shell-aplicatie]'));
        self::assertCount(0, $pagina->filter('a'));

        foreach ($pagina->filter('[src], [href]') as $resursa) {
            $adresa = $resursa->hasAttribute('src')
                ? $resursa->getAttribute('src')
                : $resursa->getAttribute('href');

            self::assertFalse(str_starts_with($adresa, 'http://'));
            self::assertFalse(str_starts_with($adresa, 'https://'));
            self::assertFalse(str_starts_with($adresa, '//'));
        }
    }

    public function testAutentificareaValidaReuseste(): void
    {
        $this->creeazaUtilizator('admin@example.com', 'parola');

        $this->trimiteFormularAutentificare('admin@example.com', 'parola');

        self::assertResponseRedirects('/');
    }

    public function testAutentificareaValidaRedirectioneazaLaPaginaPrincipala(): void
    {
        $this->creeazaUtilizator('admin@example.com', 'parola');
        $this->trimiteFormularAutentificare('admin@example.com', 'parola');

        $this->client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertRouteSame('pagina_principala');
    }

    public function testPaginaPrincipalaAfiseazaUtilizatorulAutentificat(): void
    {
        $this->creeazaUtilizator('admin@example.com', 'parola', prenume: 'Cristian', nume: 'Popa');
        $this->trimiteFormularAutentificare('admin@example.com', 'parola');

        $pagina = $this->client->followRedirect();

        self::assertCount(1, $pagina->filter('[data-shell-aplicatie][data-controller="bara-laterala"]'));
        self::assertCount(1, $pagina->filter('form[action="/deconectare"][method="post"] input[name="_csrf_token"]'));
    }

    public function testShellulAutentificatContineHeaderulCerutFaraTitlulPaginii(): void
    {
        $this->creeazaUtilizator('admin@example.com', 'parola');
        $this->trimiteFormularAutentificare('admin@example.com', 'parola');

        $pagina = $this->client->followRedirect();
        $header = $pagina->filter('header[data-header-aplicatie]');

        self::assertCount(1, $header);
        self::assertCount(1, $header->filter('.header-aplicatie__brand-extins'));
        self::assertCount(1, $header->filter('.header-aplicatie__brand-restrans'));
        self::assertCount(1, $header->filter('input[type="search"][placeholder="Caută..."]'));
        self::assertCount(1, $header->filter('button[aria-label="Notificări"]'));
        self::assertCount(1, $header->filter('button.avatar[aria-label="Meniu utilizator"]'));
        self::assertCount(0, $header->filter('h1, .page-title'));
        self::assertStringNotContainsString('Panou de control', $header->text());
    }

    public function testDropdownulAvataruluiContineNumaiFormularulPostDeDeconectareCuCsrf(): void
    {
        $this->creeazaUtilizator('admin@example.com', 'parola');
        $this->trimiteFormularAutentificare('admin@example.com', 'parola');

        $pagina = $this->client->followRedirect();
        $dropdown = $pagina->filter('.header-aplicatie__actiuni .dropdown-menu');

        self::assertCount(1, $dropdown->filter('form[action="/deconectare"][method="post"]'));
        self::assertCount(1, $dropdown->filter('input[name="_csrf_token"][value]:not([value=""])'));
        self::assertCount(1, $dropdown->filter('button[type="submit"]'));
        self::assertCount(0, $dropdown->filter('a'));
        self::assertSelectorTextSame('.header-aplicatie__actiuni .dropdown-item', 'Deconectare');
    }

    public function testNavigatiaMarcheazaRutaCurentaCaActiva(): void
    {
        $this->creeazaUtilizator('admin@example.com', 'parola');
        $this->trimiteFormularAutentificare('admin@example.com', 'parola');

        $pagina = $this->client->followRedirect();

        self::assertCount(1, $pagina->filter('.bara-laterala.navbar-expand-md:not(.navbar-vertical)'));
        self::assertCount(1, $pagina->filter('.bara-laterala__navigatie .nav-link.active[aria-current="page"][aria-label="Panou de control"][href="/"]'));
        self::assertSelectorTextSame('.bara-laterala__navigatie .nav-link.active', 'Panou de control');
        self::assertCount(0, $pagina->filter('.shell-aplicatie__continut .container-xl'));
        self::assertCount(1, $pagina->filter('.shell-aplicatie__continut > turbo-frame > .page-title'));
    }

    public function testShellulContineControaleleStimulusPentruRestrangere(): void
    {
        $this->creeazaUtilizator('admin@example.com', 'parola');
        $this->trimiteFormularAutentificare('admin@example.com', 'parola');

        $pagina = $this->client->followRedirect();

        self::assertCount(1, $pagina->filter('[data-controller="bara-laterala"]'));
        self::assertCount(1, $pagina->filter('button[data-action="bara-laterala#comuta"][data-bara-laterala-target="control"][aria-label="Restrânge"]'));
        self::assertCount(1, $pagina->filter('[data-bara-laterala-target="text"]'));
        self::assertCount(1, $pagina->filter('#navigatie-principala.collapse'));
    }

    public function testParolaGresitaEsteRefuzata(): void
    {
        $this->creeazaUtilizator('admin@example.com', 'parola');

        $this->trimiteFormularAutentificare('admin@example.com', 'gresita');

        self::assertResponseRedirects('/autentificare');
        $pagina = $this->client->followRedirect();
        self::assertSame(
            'Adresa de email sau parola este incorectă.',
            $pagina->filter('.alert[role="alert"]')->text(),
        );
        $this->client->request('GET', '/');
        self::assertResponseRedirects('/autentificare');
    }

    public function testUtilizatorulInactivEsteRefuzat(): void
    {
        $this->creeazaUtilizator('inactiv@example.com', 'parola', activ: false);

        $this->trimiteFormularAutentificare('inactiv@example.com', 'parola');

        self::assertResponseRedirects('/autentificare');
        $this->client->request('GET', '/');
        self::assertResponseRedirects('/autentificare');
    }

    public function testTokenulCsrfLipsaEsteRefuzat(): void
    {
        $this->creeazaUtilizator('admin@example.com', 'parola');

        $this->client->request('POST', '/autentificare', [
            'email' => 'admin@example.com',
            'parola' => 'parola',
        ]);

        self::assertResponseRedirects('/autentificare');
        $this->client->request('GET', '/');
        self::assertResponseRedirects('/autentificare');
    }

    public function testTokenulCsrfInvalidEsteRefuzat(): void
    {
        $this->creeazaUtilizator('admin@example.com', 'parola');

        $this->client->request('POST', '/autentificare', [
            'email' => 'admin@example.com',
            'parola' => 'parola',
            '_csrf_token' => 'token-invalid',
        ]);

        self::assertResponseRedirects('/autentificare');
        $this->client->request('GET', '/');
        self::assertResponseRedirects('/autentificare');
    }

    public function testUtilizatorulAutentificatEsteRedirectionatDeLaAutentificare(): void
    {
        $this->creeazaUtilizator('admin@example.com', 'parola');
        $this->trimiteFormularAutentificare('admin@example.com', 'parola');
        $this->client->followRedirect();

        $this->client->request('GET', '/autentificare');

        self::assertResponseRedirects('/');
    }

    public function testDeconectareaValidaInvalideazaSesiunea(): void
    {
        $this->creeazaUtilizator('admin@example.com', 'parola');
        $this->trimiteFormularAutentificare('admin@example.com', 'parola');
        $pagina = $this->client->followRedirect();

        $this->client->submit($pagina->selectButton('Deconectare')->form());

        self::assertResponseRedirects('/autentificare');
        $this->client->request('GET', '/');
        self::assertResponseRedirects('/autentificare');
    }

    public function testDeconectareaNuTransferaMesajeleAplicatieiInPaginaDeAutentificare(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', 'parola', roluri: ['ROLE_ADMIN']);
        $id = $administrator->getId();
        self::assertNotNull($id);
        $this->trimiteFormularAutentificare('admin@example.com', 'parola');
        $this->client->followRedirect();

        $paginaEditare = $this->client->request('GET', sprintf('/administrare/utilizatori/%d/editeaza', $id));
        $tokenDeconectare = $paginaEditare->filter('form[action="/deconectare"] input[name="_csrf_token"]')->attr('value');
        $formularEditare = $paginaEditare->selectButton('Salvează modificările')->form([
            'editare_utilizator[prenume]' => 'Admin',
            'editare_utilizator[nume]' => 'Actualizat',
            'editare_utilizator[email]' => 'admin@example.com',
            'editare_utilizator[administrator]' => true,
            'editare_utilizator[activ]' => true,
        ]);
        $this->client->submit($formularEditare);
        self::assertResponseRedirects('/administrare/utilizatori');

        $this->client->request('POST', '/deconectare', ['_csrf_token' => $tokenDeconectare]);
        self::assertResponseRedirects('/autentificare');
        $paginaAutentificare = $this->client->followRedirect();

        self::assertCount(0, $paginaAutentificare->filter('[data-mesaje-flash]'));
        self::assertStringNotContainsString('Utilizatorul a fost actualizat.', $paginaAutentificare->text());
    }

    public function testDeconectareaFaraCsrfEsteRefuzata(): void
    {
        $this->creeazaUtilizator('admin@example.com', 'parola');
        $this->trimiteFormularAutentificare('admin@example.com', 'parola');
        $this->client->followRedirect();

        $this->client->request('POST', '/deconectare');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
    }

    public function testDeconectareaPrinGetNuEstePermisa(): void
    {
        $this->creeazaUtilizator('admin@example.com', 'parola');
        $this->trimiteFormularAutentificare('admin@example.com', 'parola');
        $this->client->followRedirect();

        $this->client->request('GET', '/deconectare');

        self::assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testDupaDeconectarePaginaPrincipalaEsteDinNouProtejata(): void
    {
        $this->creeazaUtilizator('admin@example.com', 'parola');
        $this->trimiteFormularAutentificare('admin@example.com', 'parola');
        $pagina = $this->client->followRedirect();
        $this->client->submit($pagina->selectButton('Deconectare')->form());

        $this->client->request('GET', '/');

        self::assertResponseRedirects('/autentificare');
    }

    public function testLimitareaAutentificariiFunctioneazaDeterminist(): void
    {
        $this->creeazaUtilizator('admin@example.com', 'parola');

        $this->trimiteFormularAutentificare('admin@example.com', 'gresita', '192.0.2.80');
        self::assertResponseRedirects('/autentificare');
        $this->trimiteFormularAutentificare('admin@example.com', 'gresita', '192.0.2.80');
        self::assertResponseRedirects('/autentificare');
        $this->trimiteFormularAutentificare('admin@example.com', 'gresita', '192.0.2.80');
        self::assertResponseRedirects('/autentificare');

        $pagina = $this->client->followRedirect();

        self::assertStringContainsString('Prea multe încercări', $pagina->filter('[role="alert"]')->text());
    }

    private function trimiteFormularAutentificare(string $email, string $parola, string $ip = '192.0.2.70'): void
    {
        $pagina = $this->client->request('GET', '/autentificare', server: ['REMOTE_ADDR' => $ip]);
        $formular = $pagina->selectButton('Autentificare')->form([
            'email' => $email,
            'parola' => $parola,
        ]);

        $this->client->submit($formular, serverParameters: ['REMOTE_ADDR' => $ip]);
    }

    /** @param list<string> $roluri */
    private function creeazaUtilizator(
        string $email,
        string $parola,
        bool $activ = true,
        string $prenume = 'Utilizator',
        string $nume = 'Test',
        array $roluri = ['ROLE_USER'],
    ): Utilizator {
        $utilizator = (new Utilizator())
            ->setEmail($email)
            ->setPrenume($prenume)
            ->setNume($nume)
            ->setRoluri($roluri)
            ->setActiv($activ);
        $utilizator->setParola($this->hasherParole->hashPassword($utilizator, $parola));
        $this->managerEntitati->persist($utilizator);
        $this->managerEntitati->flush();

        return $utilizator;
    }

    private function curataLimitatorulAutentificarii(): void
    {
        $poolCache = static::getContainer()->get('cache.autentificare_limiter');

        self::assertInstanceOf(CacheItemPoolInterface::class, $poolCache);
        $poolCache->clear();
    }
}
