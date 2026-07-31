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
    }

    public function testPaginaDeAutentificareRaspundeCuHtml(): void
    {
        $pagina = $this->client->request('GET', '/autentificare');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'text/html; charset=UTF-8');
        self::assertCount(1, $pagina->filter('body.layout-securitate'));
        self::assertCount(1, $pagina->filter('main.pagina-autentificare[data-layout="securitate"]'));
        self::assertCount(1, $pagina->filter('.pagina-autentificare__imagine img[src^="/build/images/"]'));
    }

    public function testFormularulContineCampurileNecesare(): void
    {
        $pagina = $this->client->request('GET', '/autentificare');

        self::assertCount(1, $pagina->filter('input[name="email"][autocomplete="username"]'));
        self::assertCount(1, $pagina->filter('input[name="parola"][autocomplete="current-password"]'));
        self::assertCount(1, $pagina->filter('input[name="_csrf_token"]'));
        self::assertCount(1, $pagina->filter('form[action="/autentificare"][method="post"][novalidate][data-controller="validare-autentificare"]'));
        self::assertCount(2, $pagina->filter('input[required][data-validare-autentificare-target="camp"]'));
        self::assertCount(2, $pagina->filter('.invalid-feedback'));
    }

    public function testPaginaDeAutentificareNuContineShellSauResurseExterne(): void
    {
        $pagina = $this->client->request('GET', '/autentificare');

        self::assertCount(0, $pagina->filter('.navbar, .sidebar, [data-shell-aplicatie]'));

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

        self::assertSelectorTextContains('body', 'Cristian Popa');
        self::assertSelectorTextContains('body', 'admin@example.com');
        self::assertCount(1, $pagina->filter('form[action="/deconectare"][method="post"] input[name="_csrf_token"]'));
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

    private function creeazaUtilizator(
        string $email,
        string $parola,
        bool $activ = true,
        string $prenume = 'Utilizator',
        string $nume = 'Test',
    ): Utilizator {
        $utilizator = (new Utilizator())
            ->setEmail($email)
            ->setPrenume($prenume)
            ->setNume($nume)
            ->setRoluri(['ROLE_USER'])
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
