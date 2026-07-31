<?php

namespace App\Tests\Api\V1;

use App\Entity\Utilizator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;

final class AutorizareApiV1Test extends TestFunctionalApiV1
{
    private KernelBrowser $client;
    private EntityManagerInterface $managerEntitati;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->creeazaClientCuLimitatorGol();
        $this->managerEntitati = static::getContainer()->get(EntityManagerInterface::class);
        $this->managerEntitati->createQuery('DELETE FROM App\\Entity\\Utilizator')->execute();
        $this->managerEntitati->clear();
    }

    public function testFaraAutentificareRaspunde401Json(): void
    {
        $this->client->request('GET', '/api/v1/test/autorizare');

        $this->assertEroare(Response::HTTP_UNAUTHORIZED, 'autentificare_necesara', 'Autentificarea este necesara pentru accesarea acestei resurse.');
    }

    public function testUtilizatorulObisnuitRaspunde403JsonCuContractulApiSiIdCorelare(): void
    {
        $this->client->loginUser($this->creeazaUtilizator(['ROLE_USER']));
        $this->client->request('GET', '/api/v1/test/autorizare');

        $this->assertEroare(Response::HTTP_FORBIDDEN, 'acces_interzis', 'Nu aveti permisiunea necesara pentru accesarea acestei resurse.');
        self::assertNotEmpty($this->client->getResponse()->headers->get('X-Correlation-Id'));
    }

    public function testAdministratorulPrimesteAcces(): void
    {
        $this->client->loginUser($this->creeazaUtilizator(['ROLE_ADMIN']));
        $this->client->request('GET', '/api/v1/test/autorizare');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString('{"acces":"permis"}', (string) $this->client->getResponse()->getContent());
    }

    public function testRutelePubliceExistenteRamanPublice(): void
    {
        $this->client->request('GET', '/api/v1/stare');

        self::assertResponseIsSuccessful();
    }

    public function testRutaApiInexistentaPastreazaRaspunsul404Existent(): void
    {
        $this->client->request('GET', '/api/v1/inexistenta');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        self::assertJsonStringEqualsJsonString('{"cod":404,"eroare":"resursa_negasita","mesaj":"Resursa solicitata nu a fost gasita."}', (string) $this->client->getResponse()->getContent());
    }

    /** @param list<string> $roluri */
    private function creeazaUtilizator(array $roluri): Utilizator
    {
        $utilizator = (new Utilizator())
            ->setEmail('utilizator'.bin2hex(random_bytes(4)).'@example.com')
            ->setPrenume('Utilizator')
            ->setNume('Test')
            ->setParola('hash-nefolosit')
            ->setRoluri($roluri);
        $this->managerEntitati->persist($utilizator);
        $this->managerEntitati->flush();

        return $utilizator;
    }

    private function assertEroare(int $cod, string $eroare, string $mesaj): void
    {
        self::assertResponseStatusCodeSame($cod);
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertJsonStringEqualsJsonString(json_encode([
            'cod' => $cod,
            'eroare' => $eroare,
            'mesaj' => $mesaj,
        ], JSON_THROW_ON_ERROR), (string) $this->client->getResponse()->getContent());
    }
}
