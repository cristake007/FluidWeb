<?php

namespace App\Tests\Administrare;

use App\Entity\Utilizator;
use App\Repository\UtilizatorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreareUtilizatorTest extends WebTestCase
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

    public function testNumaiAdministratorulPoateAccesaFormularul(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $utilizator = $this->creeazaUtilizator('utilizator@example.com', []);

        $this->client->loginUser($administrator);
        $pagina = $this->client->request('GET', '/administrare/utilizatori/nou');

        self::assertResponseIsSuccessful();
        self::assertRouteSame('administrare_utilizator_nou');
        self::assertCount(1, $pagina->filter('form[action="/administrare/utilizatori/nou"][method="post"]'));
        self::assertCount(1, $pagina->filter('input[name="utilizator_nou[prenume]"]'));
        self::assertCount(1, $pagina->filter('input[name="utilizator_nou[nume]"]'));
        self::assertCount(1, $pagina->filter('input[name="utilizator_nou[email]"][type="email"]'));
        self::assertCount(1, $pagina->filter('input[name="utilizator_nou[administrator]"][type="checkbox"]'));
        self::assertCount(1, $pagina->filter('button.btn.btn-primary.btn-sm[type="submit"] .ti.ti-user-plus.me-1'));
        self::assertCount(1, $pagina->filter('a.btn.btn-link.btn-sm.text-danger[href="/administrare/utilizatori"]'));

        $this->client->loginUser($utilizator);
        $this->client->request('GET', '/administrare/utilizatori/nou');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->client->request('POST', '/administrare/utilizatori/nou', [
            'utilizator_nou' => [
                'prenume' => 'Neautorizat',
                'nume' => 'Test',
                'email' => 'neautorizat@example.com',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testValideazaCampurileObligatoriiSiEmailul(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $this->client->loginUser($administrator);
        $pagina = $this->client->request('GET', '/administrare/utilizatori/nou');

        $formular = $pagina->selectButton('Creează utilizatorul')->form([
            'utilizator_nou[prenume]' => '',
            'utilizator_nou[nume]' => '',
            'utilizator_nou[email]' => 'email-invalid',
        ]);
        $pagina = $this->client->submit($formular);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertStringContainsString('Completați prenumele.', $pagina->text());
        self::assertStringContainsString('Completați numele.', $pagina->text());
        self::assertStringContainsString('Introduceți o adresă de email validă.', $pagina->text());
        self::assertCount(1, $pagina->filter('input[name="utilizator_nou[prenume]"].is-invalid'));
        self::assertCount(1, $pagina->filter('input[name="utilizator_nou[nume]"].is-invalid'));
        self::assertCount(1, $pagina->filter('input[name="utilizator_nou[email]"].is-invalid'));
        self::assertCount(3, $pagina->filter('.invalid-feedback'));
        self::assertNull($this->utilizatorRepository->findOneBy(['email' => 'email-invalid']));
    }

    public function testRefuzaEmailulDuplicat(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $this->creeazaUtilizator('existent@example.com', []);
        $this->client->loginUser($administrator);
        $pagina = $this->client->request('GET', '/administrare/utilizatori/nou');

        $formular = $pagina->selectButton('Creează utilizatorul')->form([
            'utilizator_nou[prenume]' => 'Elena',
            'utilizator_nou[nume]' => 'Popescu',
            'utilizator_nou[email]' => 'EXISTENT@example.com',
        ]);
        $pagina = $this->client->submit($formular);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertStringContainsString('Există deja un utilizator cu această adresă de email.', $pagina->text());
        self::assertCount(1, $this->utilizatorRepository->findBy(['email' => 'existent@example.com']));
    }

    public function testCreeazaUtilizatorObisnuitCuParolaSiguraHashuita(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $this->client->loginUser($administrator);
        $pagina = $this->client->request('GET', '/administrare/utilizatori/nou');

        $formular = $pagina->selectButton('Creează utilizatorul')->form([
            'utilizator_nou[prenume]' => 'Elena',
            'utilizator_nou[nume]' => 'Popescu',
            'utilizator_nou[email]' => 'elena@example.com',
        ]);
        $this->client->submit($formular);

        self::assertResponseRedirects('/administrare/utilizatori/nou');
        $rezultat = $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.alert', 'Copiați parola acum. Nu va mai fi afișată.');
        self::assertCount(1, $rezultat->filter('a.btn[href="/administrare/utilizatori"]'));
        self::assertCount(1, $rezultat->filter('[data-controller="copiere-clipboard"]'));
        self::assertCount(1, $rezultat->filter('button[data-action="copiere-clipboard#copiaza"][aria-label="Copiază parola"] .ti.ti-copy'));

        $parola = trim($rezultat->filter('[data-parola-generata]')->text());
        self::assertGreaterThanOrEqual(24, mb_strlen($parola));

        $utilizator = $this->utilizatorRepository->findOneBy(['email' => 'elena@example.com']);
        self::assertNotNull($utilizator);
        self::assertSame(['ROLE_USER'], $utilizator->getRoles());
        self::assertTrue($utilizator->esteActiv());
        self::assertNotSame($parola, $utilizator->getPassword());
        self::assertTrue($this->hasherParole->isPasswordValid($utilizator, $parola));
    }

    public function testSalveazaRolulAdministratorCandOptiuneaEsteBifata(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $this->client->loginUser($administrator);
        $pagina = $this->client->request('GET', '/administrare/utilizatori/nou');

        $formular = $pagina->selectButton('Creează utilizatorul')->form([
            'utilizator_nou[prenume]' => 'Andrei',
            'utilizator_nou[nume]' => 'Admin',
            'utilizator_nou[email]' => 'andrei@example.com',
            'utilizator_nou[administrator]' => true,
        ]);
        $this->client->submit($formular);

        self::assertResponseRedirects('/administrare/utilizatori/nou');
        $utilizator = $this->utilizatorRepository->findOneBy(['email' => 'andrei@example.com']);
        self::assertNotNull($utilizator);
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $utilizator->getRoles());
    }

    public function testParolaNuMaiEsteAfisataLaUnAccesUlterior(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $this->client->loginUser($administrator);
        $pagina = $this->client->request('GET', '/administrare/utilizatori/nou');
        $formular = $pagina->selectButton('Creează utilizatorul')->form([
            'utilizator_nou[prenume]' => 'Maria',
            'utilizator_nou[nume]' => 'Ionescu',
            'utilizator_nou[email]' => 'maria@example.com',
        ]);
        $this->client->submit($formular);
        $rezultat = $this->client->followRedirect();
        $parola = trim($rezultat->filter('[data-parola-generata]')->text());

        $paginaUlterioara = $this->client->request('GET', '/administrare/utilizatori/nou');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $paginaUlterioara->filter('form[name="utilizator_nou"]'));
        self::assertCount(0, $paginaUlterioara->filter('[data-parola-generata]'));
        self::assertStringNotContainsString('Copiați parola acum. Nu va mai fi afișată.', $paginaUlterioara->text());
        self::assertStringNotContainsString($parola, (string) $this->client->getResponse()->getContent());
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
