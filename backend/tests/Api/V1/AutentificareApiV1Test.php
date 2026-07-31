<?php

namespace App\Tests\Api\V1;

use App\Entity\Utilizator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class AutentificareApiV1Test extends TestFunctionalApiV1
{
    private KernelBrowser $client;
    private EntityManagerInterface $managerEntitati;
    private UserPasswordHasherInterface $hasherParole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->creeazaClientCuLimitatorGol();
        $container = static::getContainer();
        $this->managerEntitati = $container->get(EntityManagerInterface::class);
        $this->hasherParole = $container->get(UserPasswordHasherInterface::class);
        $this->managerEntitati->createQuery('DELETE FROM App\\Entity\\Utilizator')->execute();
        $this->managerEntitati->clear();
    }

    public function testEndpointulTokenCsrfSeteazaCookieulXsrfToken(): void
    {
        $client = $this->client;
        $client->request('GET', '/api/v1/token-csrf');

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $cookie = $client->getCookieJar()->get('XSRF-TOKEN');
        self::assertInstanceOf(Cookie::class, $cookie);
        self::assertFalse($cookie->isHttpOnly());
        self::assertSame('/', $cookie->getPath());
        self::assertSame('lax', $cookie->getSameSite());
    }

    public function testAutentificareaFaraCsrfReturneaza403(): void
    {
        $client = $this->client;
        $this->creeazaUtilizator('admin@example.com', 'parola');

        $this->cereAutentificare($client, 'admin@example.com', 'parola');

        $this->assertEroare($client, Response::HTTP_FORBIDDEN, 'acces_interzis', 'Nu aveti permisiunea necesara pentru accesarea acestei resurse.');
    }

    public function testAutentificareaCuTokenCsrfInvalidReturneaza403(): void
    {
        $client = $this->client;
        $this->creeazaUtilizator('admin@example.com', 'parola');
        $this->obtineTokenCsrf($client);

        $this->cereAutentificare($client, 'admin@example.com', 'parola', 'invalid');

        $this->assertEroare($client, Response::HTTP_FORBIDDEN, 'acces_interzis', 'Nu aveti permisiunea necesara pentru accesarea acestei resurse.');
    }

    public function testAutentificareaCuTokenCsrfValidReusesteSiReturneazaDateSigure(): void
    {
        $client = $this->client;
        $utilizator = $this->creeazaUtilizator('admin@example.com', 'parola', ['ROLE_ADMIN']);
        $token = $this->obtineTokenCsrf($client);

        $this->cereAutentificare($client, 'admin@example.com', 'parola', $token);

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(json_encode([
            'utilizator' => [
                'id' => $utilizator->getId(),
                'email' => 'admin@example.com',
                'prenume' => 'Cristian',
                'nume' => 'Popa',
                'roluri' => ['ROLE_ADMIN', 'ROLE_USER'],
                'activ' => true,
            ],
        ], JSON_THROW_ON_ERROR), (string) $client->getResponse()->getContent());
        self::assertStringNotContainsString('"parola"', (string) $client->getResponse()->getContent());
        self::assertStringNotContainsString($utilizator->getPassword(), (string) $client->getResponse()->getContent());
    }

    public function testEmailulEsteNormalizatFaraDiferentaIntreLitereMariSiMici(): void
    {
        $client = $this->client;
        $this->creeazaUtilizator('admin@example.com', 'parola');
        $token = $this->obtineTokenCsrf($client);

        $this->cereAutentificare($client, 'ADMIN@EXAMPLE.COM', 'parola', $token);

        self::assertResponseIsSuccessful();
    }

    public function testParolaGresitaEmailulInexistentSiUtilizatorulInactivAuAcelasiRaspunsGeneric(): void
    {
        $client = $this->client;
        $this->creeazaUtilizator('admin@example.com', 'parola');
        $this->creeazaUtilizator('inactiv@example.com', 'parola', activ: false);
        $token = $this->obtineTokenCsrf($client);

        $this->cereAutentificare($client, 'admin@example.com', 'gresita', $token);
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $raspunsParolaGresita = (string) $client->getResponse()->getContent();

        $this->cereAutentificare($client, 'inexistent@example.com', 'parola', $token);
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        self::assertSame($raspunsParolaGresita, (string) $client->getResponse()->getContent());

        $this->cereAutentificare($client, 'inactiv@example.com', 'parola', $token);
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        self::assertSame($raspunsParolaGresita, (string) $client->getResponse()->getContent());
    }

    public function testUtilizatorulCurentNecesitaSesiuneSiReturneazaUtilizatorulAutentificat(): void
    {
        $client = $this->client;
        $client->request('GET', '/api/v1/utilizator-curent');
        $this->assertEroare($client, Response::HTTP_UNAUTHORIZED, 'autentificare_necesara', 'Autentificarea este necesara pentru accesarea acestei resurse.');

        $utilizator = $this->creeazaUtilizator('admin@example.com', 'parola');
        $token = $this->obtineTokenCsrf($client);
        $this->cereAutentificare($client, 'admin@example.com', 'parola', $token);
        self::assertResponseIsSuccessful();

        $client->request('GET', '/api/v1/utilizator-curent', server: ['REMOTE_ADDR' => '192.0.2.51']);
        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(json_encode([
            'id' => $utilizator->getId(), 'email' => 'admin@example.com', 'prenume' => 'Cristian', 'nume' => 'Popa',
            'roluri' => ['ROLE_USER'], 'activ' => true,
        ], JSON_THROW_ON_ERROR), (string) $client->getResponse()->getContent());
    }

    public function testDeconectareaFaraCsrfReturneaza403IarCeaValidaInvalideazaSesiunea(): void
    {
        $client = $this->client;
        $client->request('POST', '/api/v1/deconectare', server: ['REMOTE_ADDR' => '192.0.2.50']);
        $this->assertEroare($client, Response::HTTP_FORBIDDEN, 'acces_interzis', 'Nu aveti permisiunea necesara pentru accesarea acestei resurse.');

        $this->creeazaUtilizator('admin@example.com', 'parola');
        $token = $this->obtineTokenCsrf($client, '192.0.2.51');
        $this->cereAutentificare($client, 'admin@example.com', 'parola', $token);
        self::assertResponseIsSuccessful();
        $token = $this->obtineTokenCsrf($client, '192.0.2.54');

        $client->request('POST', '/api/v1/deconectare', server: ['HTTP_X_XSRF_TOKEN' => $token, 'REMOTE_ADDR' => '192.0.2.52']);
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $client->request('GET', '/api/v1/utilizator-curent', server: ['REMOTE_ADDR' => '192.0.2.53']);
        $this->assertEroare($client, Response::HTTP_UNAUTHORIZED, 'autentificare_necesara', 'Autentificarea este necesara pentru accesarea acestei resurse.');
    }

    public function testStareaRamanePublica(): void
    {
        $client = $this->client;
        $client->request('GET', '/api/v1/stare');

        self::assertResponseIsSuccessful();
    }

    public function testLoginThrottlingPermiteIncercarilePanaLaPragSiApoiReturneaza429(): void
    {
        $client = $this->client;
        $this->creeazaUtilizator('admin@example.com', 'parola');
        $token = $this->obtineTokenCsrf($client);

        $this->cereAutentificare($client, 'admin@example.com', 'gresita', $token, '192.0.2.61');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->cereAutentificare($client, 'admin@example.com', 'gresita', $token, '192.0.2.61');
        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        $this->cereAutentificare($client, 'admin@example.com', 'gresita', $token, '192.0.2.61');

        $this->assertEroare($client, Response::HTTP_TOO_MANY_REQUESTS, 'prea_multe_cereri', 'Prea multe cereri. Incercati din nou mai tarziu.');
        self::assertNotNull($client->getResponse()->headers->get('retry-after'));
    }

    public function testLimitareaUnuiEmailNuBlocheazaAltEmailInainteDeLimitaGlobala(): void
    {
        $client = $this->client;
        $this->creeazaUtilizator('primar@example.com', 'parola');
        $this->creeazaUtilizator('altul@example.com', 'parola');
        $token = $this->obtineTokenCsrf($client);

        $this->cereAutentificare($client, 'primar@example.com', 'gresita', $token, '192.0.2.62');
        $this->cereAutentificare($client, 'primar@example.com', 'gresita', $token, '192.0.2.62');
        $this->cereAutentificare($client, 'altul@example.com', 'gresita', $token, '192.0.2.62');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testSerializareaUtilizatoruluiNuContineHashulRealAlParolei(): void
    {
        $utilizator = $this->creeazaUtilizator('admin@example.com', 'parola');

        self::assertStringNotContainsString($utilizator->getPassword(), serialize($utilizator));
    }

    private function creeazaUtilizator(string $email, string $parola, array $roluri = [], bool $activ = true): Utilizator
    {
        $utilizator = (new Utilizator())
            ->setEmail($email)
            ->setPrenume('Cristian')
            ->setNume('Popa')
            ->setRoluri($roluri)
            ->setActiv($activ);
        $utilizator->setParola($this->hasherParole->hashPassword($utilizator, $parola));
        $this->managerEntitati->persist($utilizator);
        $this->managerEntitati->flush();

        return $utilizator;
    }

    private function obtineTokenCsrf(KernelBrowser $client, ?string $ip = null): string
    {
        $server = null === $ip ? [] : ['REMOTE_ADDR' => $ip];
        $client->request('GET', '/api/v1/token-csrf', server: $server);
        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $cookie = $client->getCookieJar()->get('XSRF-TOKEN');
        self::assertInstanceOf(Cookie::class, $cookie);

        return $cookie->getValue();
    }

    private function cereAutentificare(KernelBrowser $client, string $email, string $parola, ?string $token = null, ?string $ip = null): void
    {
        $server = ['CONTENT_TYPE' => 'application/json'];
        if (null !== $token) {
            $server['HTTP_X_XSRF_TOKEN'] = $token;
        }
        if (null !== $ip) {
            $server['REMOTE_ADDR'] = $ip;
        }

        $client->request('POST', '/api/v1/autentificare', server: $server, content: json_encode([
            'email' => $email,
            'parola' => $parola,
        ], JSON_THROW_ON_ERROR));
    }

    private function assertEroare(KernelBrowser $client, int $cod, string $eroare, string $mesaj): void
    {
        self::assertResponseStatusCodeSame($cod);
        self::assertJsonStringEqualsJsonString(json_encode([
            'cod' => $cod,
            'eroare' => $eroare,
            'mesaj' => $mesaj,
        ], JSON_THROW_ON_ERROR), (string) $client->getResponse()->getContent());
    }
}
