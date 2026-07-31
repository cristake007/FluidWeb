<?php

namespace App\Tests\Administrare;

use App\Entity\Utilizator;
use App\Repository\UtilizatorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ResetareParolaUtilizatorTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $managerEntitati;
    private UtilizatorRepository $utilizatorRepository;
    private UserPasswordHasherInterface $hasherParole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->managerEntitati = $container->get(EntityManagerInterface::class);
        $this->utilizatorRepository = $container->get(UtilizatorRepository::class);
        $this->hasherParole = $container->get(UserPasswordHasherInterface::class);
        $this->managerEntitati->createQuery('DELETE FROM App\\Entity\\Utilizator')->execute();
        $this->managerEntitati->clear();
    }

    public function testNumaiAdministratorulPoateResetaParola(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $utilizator = $this->creeazaUtilizator('utilizator@example.com', []);
        $id = $this->idUtilizator($utilizator);
        $url = sprintf('/administrare/utilizatori/%d/resetare-parola', $id);

        $this->client->request('POST', $url);
        self::assertResponseRedirects('/autentificare', Response::HTTP_FOUND);

        $this->client->loginUser($utilizator);
        $this->client->request('POST', $url);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->client->loginUser($administrator);
        $pagina = $this->client->request('GET', '/administrare/utilizatori');
        $formular = $pagina->filter(sprintf('form[action="%s"]', $url))->form();
        $this->client->submit($formular);

        self::assertResponseIsSuccessful();
        self::assertRouteSame('administrare_utilizator_resetare_parola');
    }

    public function testCerereaGetEsteRespinsa(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $utilizator = $this->creeazaUtilizator('utilizator@example.com', []);
        $this->client->loginUser($administrator);

        $this->client->request('GET', sprintf('/administrare/utilizatori/%d/resetare-parola', $this->idUtilizator($utilizator)));

        self::assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testTokenulCsrfInvalidEsteRespinsFaraSchimbareaHashului(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $utilizator = $this->creeazaUtilizator('utilizator@example.com', [], 'parola-veche');
        $id = $this->idUtilizator($utilizator);
        $hashInitial = $utilizator->getPassword();
        $this->client->loginUser($administrator);

        $this->client->request('POST', sprintf('/administrare/utilizatori/%d/resetare-parola', $id), [
            '_csrf_token' => 'token-invalid',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        $this->managerEntitati->clear();
        $utilizatorNemodificat = $this->utilizatorRepository->find($id);
        self::assertNotNull($utilizatorNemodificat);
        self::assertSame($hashInitial, $utilizatorNemodificat->getPassword());
    }

    public function testSchimbaHashulIarParolaGenerataFunctioneazaLaAutentificare(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $utilizator = $this->creeazaUtilizator('utilizator@example.com', [], 'parola-veche');
        $id = $this->idUtilizator($utilizator);
        $hashInitial = $utilizator->getPassword();
        $this->client->loginUser($administrator);

        $raspuns = $this->trimiteResetareaParolei($id);
        $parola = trim($raspuns->filter('[data-parola-generata]')->text());

        $this->managerEntitati->clear();
        $utilizatorActualizat = $this->utilizatorRepository->find($id);
        self::assertNotNull($utilizatorActualizat);

        self::assertGreaterThanOrEqual(24, mb_strlen($parola));
        self::assertNotSame($hashInitial, $utilizatorActualizat->getPassword());
        self::assertNotSame($parola, $utilizatorActualizat->getPassword());
        self::assertTrue($this->hasherParole->isPasswordValid($utilizatorActualizat, $parola));

        $this->client->restart();
        $paginaAutentificare = $this->client->request('GET', '/autentificare');
        $formular = $paginaAutentificare->selectButton('Autentificare')->form([
            'email' => 'utilizator@example.com',
            'parola' => $parola,
        ]);
        $this->client->submit($formular);

        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    public function testParolaEsteAfisataNumaiInRaspunsulImediat(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $utilizator = $this->creeazaUtilizator('utilizator@example.com', []);
        $this->client->loginUser($administrator);

        $raspuns = $this->trimiteResetareaParolei($this->idUtilizator($utilizator));
        $parola = trim($raspuns->filter('[data-parola-generata]')->text());

        self::assertSelectorTextSame('h1', 'Parolă resetată');
        self::assertSelectorTextContains('.alert', 'Copiați parola acum. Nu va mai fi afișată.');
        self::assertStringContainsString('no-store', (string) $this->client->getResponse()->headers->get('cache-control'));
        self::assertStringNotContainsString($parola, serialize($this->client->getRequest()->getSession()->all()));

        $paginaUrmatoare = $this->client->request('GET', '/administrare/utilizatori');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $paginaUrmatoare->filter('[data-parola-generata]'));
        self::assertStringNotContainsString($parola, (string) $this->client->getResponse()->getContent());
    }

    private function trimiteResetareaParolei(int $idUtilizator): \Symfony\Component\DomCrawler\Crawler
    {
        $pagina = $this->client->request('GET', '/administrare/utilizatori');
        $formular = $pagina
            ->filter(sprintf('form[action="/administrare/utilizatori/%d/resetare-parola"]', $idUtilizator))
            ->form();

        return $this->client->submit($formular);
    }

    /** @param list<string> $roluri */
    private function creeazaUtilizator(
        string $email,
        array $roluri,
        string $parola = 'parola-test',
    ): Utilizator {
        $utilizator = (new Utilizator())
            ->setEmail($email)
            ->setPrenume('Utilizator')
            ->setNume('Test')
            ->setRoluri($roluri);
        $utilizator->setParola($this->hasherParole->hashPassword($utilizator, $parola));

        $this->managerEntitati->persist($utilizator);
        $this->managerEntitati->flush();

        return $utilizator;
    }

    private function idUtilizator(Utilizator $utilizator): int
    {
        $id = $utilizator->getId();
        self::assertNotNull($id);

        return $id;
    }
}
