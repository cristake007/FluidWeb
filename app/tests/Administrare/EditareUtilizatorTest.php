<?php

namespace App\Tests\Administrare;

use App\Entity\Utilizator;
use App\Repository\UtilizatorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class EditareUtilizatorTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $managerEntitati;
    private UtilizatorRepository $utilizatorRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->managerEntitati = $container->get(EntityManagerInterface::class);
        $this->utilizatorRepository = $container->get(UtilizatorRepository::class);
        $this->managerEntitati->createQuery('DELETE FROM App\\Entity\\Utilizator')->execute();
        $this->managerEntitati->clear();
    }

    public function testNumaiAdministratorulPoateAccesaEditareaPrinGetSiPost(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $utilizator = $this->creeazaUtilizator('utilizator@example.com', []);
        $id = $utilizator->getId();
        self::assertNotNull($id);

        $this->client->request('GET', sprintf('/administrare/utilizatori/%d/editeaza', $id));
        self::assertResponseRedirects('/autentificare', Response::HTTP_FOUND);

        $this->client->loginUser($utilizator);
        $this->client->request('GET', sprintf('/administrare/utilizatori/%d/editeaza', $id));
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->client->request('POST', sprintf('/administrare/utilizatori/%d/editeaza', $id), [
            'editare_utilizator' => [
                'prenume' => 'Neautorizat',
                'nume' => 'Test',
                'email' => 'neautorizat@example.com',
            ],
        ]);
        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->client->loginUser($administrator);
        $pagina = $this->client->request('GET', sprintf('/administrare/utilizatori/%d/editeaza', $id));

        self::assertResponseIsSuccessful();
        self::assertRouteSame('administrare_utilizator_editeaza');
        self::assertSelectorTextSame('h1', 'Editează utilizatorul');
        self::assertCount(1, $pagina->filter(sprintf('form[action="/administrare/utilizatori/%d/editeaza"][method="post"]', $id)));
        self::assertCount(1, $pagina->filter('input[name="editare_utilizator[prenume]"]'));
        self::assertCount(1, $pagina->filter('input[name="editare_utilizator[nume]"]'));
        self::assertCount(1, $pagina->filter('input[name="editare_utilizator[email]"][type="email"]'));
        self::assertCount(1, $pagina->filter('input[name="editare_utilizator[administrator]"][type="checkbox"]'));
        self::assertCount(1, $pagina->filter('input[name="editare_utilizator[activ]"][type="checkbox"]'));
        self::assertCount(0, $pagina->filter('input[type="password"]'));
        self::assertCount(1, $pagina->filter('button[type="submit"] .ti.ti-device-floppy'));
    }

    public function testActualizeazaDateleRolulSiStareaDarPastreazaParola(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $utilizator = $this->creeazaUtilizator('vechi@example.com', [], true, 'hash-existent');
        $id = $utilizator->getId();
        self::assertNotNull($id);
        $this->client->loginUser($administrator);
        $pagina = $this->client->request('GET', sprintf('/administrare/utilizatori/%d/editeaza', $id));

        $formular = $pagina->selectButton('Salvează modificările')->form([
            'editare_utilizator[prenume]' => 'Elena',
            'editare_utilizator[nume]' => 'Popescu',
            'editare_utilizator[email]' => 'ELENA@example.com',
            'editare_utilizator[administrator]' => true,
            'editare_utilizator[activ]' => false,
        ]);
        $this->client->submit($formular);

        self::assertResponseRedirects('/administrare/utilizatori');
        $pagina = $this->client->followRedirect();
        self::assertSelectorTextContains('.alert.alert-success', 'Utilizatorul a fost actualizat.');

        $this->managerEntitati->clear();
        $utilizatorActualizat = $this->utilizatorRepository->find($id);
        self::assertNotNull($utilizatorActualizat);
        self::assertSame('Elena', $utilizatorActualizat->getPrenume());
        self::assertSame('Popescu', $utilizatorActualizat->getNume());
        self::assertSame('elena@example.com', $utilizatorActualizat->getEmail());
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $utilizatorActualizat->getRoles());
        self::assertFalse($utilizatorActualizat->esteActiv());
        self::assertSame('hash-existent', $utilizatorActualizat->getPassword());
    }

    public function testAcceptaEmailulNeschimbatDarRefuzaEmailulAltuia(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $utilizator = $this->creeazaUtilizator('editat@example.com', []);
        $this->creeazaUtilizator('existent@example.com', []);
        $id = $utilizator->getId();
        self::assertNotNull($id);
        $this->client->loginUser($administrator);

        $pagina = $this->client->request('GET', sprintf('/administrare/utilizatori/%d/editeaza', $id));
        $formular = $pagina->selectButton('Salvează modificările')->form([
            'editare_utilizator[prenume]' => 'Prenume',
            'editare_utilizator[nume]' => 'Schimbat',
            'editare_utilizator[email]' => 'EDITAT@example.com',
            'editare_utilizator[activ]' => true,
        ]);
        $this->client->submit($formular);
        self::assertResponseRedirects('/administrare/utilizatori');

        $pagina = $this->client->request('GET', sprintf('/administrare/utilizatori/%d/editeaza', $id));
        $formular = $pagina->selectButton('Salvează modificările')->form([
            'editare_utilizator[prenume]' => 'Prenume',
            'editare_utilizator[nume]' => 'Schimbat',
            'editare_utilizator[email]' => 'EXISTENT@example.com',
            'editare_utilizator[activ]' => true,
        ]);
        $pagina = $this->client->submit($formular);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertStringContainsString('Există deja un utilizator cu această adresă de email.', $pagina->text());
        self::assertCount(1, $pagina->filter('input[name="editare_utilizator[email]"].is-invalid'));

        $this->managerEntitati->clear();
        $utilizatorNemodificat = $this->utilizatorRepository->find($id);
        self::assertNotNull($utilizatorNemodificat);
        self::assertSame('editat@example.com', $utilizatorNemodificat->getEmail());
    }

    public function testAdministratorulNuIsiPoateEliminaRolulSauDezactivaContul(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $id = $administrator->getId();
        self::assertNotNull($id);
        $this->client->loginUser($administrator);
        $pagina = $this->client->request('GET', sprintf('/administrare/utilizatori/%d/editeaza', $id));

        $formular = $pagina->selectButton('Salvează modificările')->form([
            'editare_utilizator[prenume]' => 'Admin',
            'editare_utilizator[nume]' => 'Propriu',
            'editare_utilizator[email]' => 'admin@example.com',
            'editare_utilizator[administrator]' => false,
            'editare_utilizator[activ]' => false,
        ]);
        $pagina = $this->client->submit($formular);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertStringContainsString('Nu vă puteți elimina propriul rol de administrator.', $pagina->text());
        self::assertStringContainsString('Nu vă puteți dezactiva propriul cont.', $pagina->text());
        self::assertCount(1, $pagina->filter('input[name="editare_utilizator[administrator]"].is-invalid'));
        self::assertCount(1, $pagina->filter('input[name="editare_utilizator[activ]"].is-invalid'));

        $this->managerEntitati->clear();
        $administratorNemodificat = $this->utilizatorRepository->find($id);
        self::assertNotNull($administratorNemodificat);
        self::assertContains('ROLE_ADMIN', $administratorNemodificat->getRoles());
        self::assertTrue($administratorNemodificat->esteActiv());
    }

    /** @param list<string> $roluri */
    private function creeazaUtilizator(
        string $email,
        array $roluri,
        bool $activ = true,
        string $parola = 'hash-test',
    ): Utilizator {
        $utilizator = (new Utilizator())
            ->setEmail($email)
            ->setPrenume('Utilizator')
            ->setNume('Test')
            ->setRoluri($roluri)
            ->setActiv($activ)
            ->setParola($parola);

        $this->managerEntitati->persist($utilizator);
        $this->managerEntitati->flush();

        return $utilizator;
    }
}
