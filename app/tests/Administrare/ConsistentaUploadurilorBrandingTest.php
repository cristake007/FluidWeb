<?php

namespace App\Tests\Administrare;

use App\Entity\Utilizator;
use App\Tests\Dublura\StocareFisiereBrandingControlata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ConsistentaUploadurilorBrandingTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $managerEntitati;
    private string $directorBranding;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->managerEntitati = static::getContainer()->get(EntityManagerInterface::class);
        $directorBranding = static::getContainer()->getParameter('director_branding');
        self::assertIsString($directorBranding);
        $this->directorBranding = $directorBranding;
        $this->stergeFisiereleDeTest();
        $this->managerEntitati->createQuery('DELETE FROM App\\Entity\\ConfiguratieBranding')->execute();
        $this->managerEntitati->createQuery('DELETE FROM App\\Entity\\Utilizator')->execute();
        $this->managerEntitati->clear();
    }

    public function testEsecLaAlDoileaUploadStergeFisierulNouSiPastreazaFisiereleVechi(): void
    {
        $administrator = $this->creeazaAdministrator();
        $this->client->loginUser($administrator);
        $fisiereVechi = [$this->creeazaImaginePng(), $this->creeazaImaginePng()];

        try {
            $pagina = $this->client->request('GET', '/administrare/branding');
            $formular = $pagina->selectButton('Salvează brandingul')->form();
            $formular['configuratie_branding[fisierLogoPrincipal]']->upload($fisiereVechi[0]);
            $formular['configuratie_branding[fisierLogoCompact]']->upload($fisiereVechi[1]);
            $this->client->submit($formular);
        } finally {
            array_map('unlink', $fisiereVechi);
        }
        self::assertResponseRedirects('/administrare/branding', Response::HTTP_SEE_OTHER);
        $fisierePersistenteVechi = $this->listeazaFisiereleBranding();
        self::assertCount(2, $fisierePersistenteVechi);

        $stocareControlata = static::getContainer()->get(StocareFisiereBrandingControlata::class);
        $stocareControlata->esueazaLaSalvarea(2);
        $fisiereNoi = [$this->creeazaImaginePng(), $this->creeazaImaginePng()];
        $pagina = $this->client->followRedirect();
        $formular = $pagina->selectButton('Salvează brandingul')->form();
        $formular['configuratie_branding[fisierLogoPrincipal]']->upload($fisiereNoi[0]);
        $formular['configuratie_branding[fisierLogoCompact]']->upload($fisiereNoi[1]);
        $this->client->catchExceptions(false);

        try {
            $this->client->submit($formular);
            self::fail('Al doilea upload trebuia să eșueze.');
        } catch (\RuntimeException $exceptie) {
            self::assertSame('Eșec de stocare simulat.', $exceptie->getMessage());
        } finally {
            $this->client->catchExceptions(true);
            array_map('unlink', $fisiereNoi);
        }

        self::assertSame($fisierePersistenteVechi, $this->listeazaFisiereleBranding());
        foreach ($fisierePersistenteVechi as $fisierVechi) {
            self::assertFileExists($fisierVechi);
        }
    }

    public function testEsecLaFlushStergeToateFisiereleNoiSiPastreazaFisiereleVechi(): void
    {
        $administrator = $this->creeazaAdministrator();
        $this->client->loginUser($administrator);
        $fisiereVechi = [$this->creeazaImaginePng(), $this->creeazaImaginePng()];

        try {
            $pagina = $this->client->request('GET', '/administrare/branding');
            $formular = $pagina->selectButton('Salvează brandingul')->form();
            $formular['configuratie_branding[fisierLogoPrincipal]']->upload($fisiereVechi[0]);
            $formular['configuratie_branding[fisierLogoCompact]']->upload($fisiereVechi[1]);
            $this->client->submit($formular);
        } finally {
            array_map('unlink', $fisiereVechi);
        }
        self::assertResponseRedirects('/administrare/branding', Response::HTTP_SEE_OTHER);
        $fisierePersistenteVechi = $this->listeazaFisiereleBranding();
        self::assertCount(2, $fisierePersistenteVechi);

        $ascultatorFlush = new class {
            public function preFlush(PreFlushEventArgs $eveniment): void
            {
                throw new \RuntimeException('Eșec de flush simulat.');
            }
        };
        $managerEvenimente = $this->managerEntitati->getEventManager();
        $managerEvenimente->addEventListener([Events::preFlush], $ascultatorFlush);
        $fisiereNoi = [$this->creeazaImaginePng(), $this->creeazaImaginePng()];
        $pagina = $this->client->followRedirect();
        $formular = $pagina->selectButton('Salvează brandingul')->form();
        $formular['configuratie_branding[fisierLogoPrincipal]']->upload($fisiereNoi[0]);
        $formular['configuratie_branding[fisierLogoCompact]']->upload($fisiereNoi[1]);
        $this->client->catchExceptions(false);

        try {
            $this->client->submit($formular);
            self::fail('Flush-ul trebuia să eșueze.');
        } catch (\RuntimeException $exceptie) {
            self::assertSame('Eșec de flush simulat.', $exceptie->getMessage());
        } finally {
            $this->client->catchExceptions(true);
            $managerEvenimente->removeEventListener([Events::preFlush], $ascultatorFlush);
            array_map('unlink', $fisiereNoi);
        }

        self::assertSame($fisierePersistenteVechi, $this->listeazaFisiereleBranding());
        foreach ($fisierePersistenteVechi as $fisierVechi) {
            self::assertFileExists($fisierVechi);
        }
    }

    public function testFluxulNormalInlocuiesteSiStergeFisiereleVechi(): void
    {
        $administrator = $this->creeazaAdministrator();
        $this->client->loginUser($administrator);
        $surseVechi = [$this->creeazaImaginePng(), $this->creeazaImaginePng(), $this->creeazaImaginePng()];

        try {
            $pagina = $this->client->request('GET', '/administrare/branding');
            $formular = $pagina->selectButton('Salvează brandingul')->form();
            $formular['configuratie_branding[fisierLogoPrincipal]']->upload($surseVechi[0]);
            $formular['configuratie_branding[fisierLogoCompact]']->upload($surseVechi[1]);
            $formular['configuratie_branding[fisierFavicon]']->upload($surseVechi[2]);
            $this->client->submit($formular);
        } finally {
            array_map('unlink', $surseVechi);
        }
        self::assertResponseRedirects('/administrare/branding', Response::HTTP_SEE_OTHER);
        $fisierePersistenteVechi = $this->listeazaFisiereleBranding();
        self::assertCount(3, $fisierePersistenteVechi);

        $surseNoi = [$this->creeazaImaginePng(), $this->creeazaImaginePng(), $this->creeazaImaginePng()];
        $pagina = $this->client->followRedirect();
        $formular = $pagina->selectButton('Salvează brandingul')->form();
        $formular['configuratie_branding[fisierLogoPrincipal]']->upload($surseNoi[0]);
        $formular['configuratie_branding[fisierLogoCompact]']->upload($surseNoi[1]);
        $formular['configuratie_branding[fisierFavicon]']->upload($surseNoi[2]);

        try {
            $this->client->submit($formular);
        } finally {
            array_map('unlink', $surseNoi);
        }

        self::assertResponseRedirects('/administrare/branding', Response::HTTP_SEE_OTHER);
        $fisierePersistenteNoi = $this->listeazaFisiereleBranding();
        self::assertCount(3, $fisierePersistenteNoi);
        self::assertSame([], array_intersect($fisierePersistenteVechi, $fisierePersistenteNoi));
        foreach ($fisierePersistenteVechi as $fisierVechi) {
            self::assertFileDoesNotExist($fisierVechi);
        }
        foreach ($fisierePersistenteNoi as $fisierNou) {
            self::assertFileExists($fisierNou);
        }
    }

    private function creeazaAdministrator(): Utilizator
    {
        $administrator = (new Utilizator())
            ->setEmail('admin@example.com')
            ->setPrenume('Administrator')
            ->setNume('Test')
            ->setRoluri(['ROLE_ADMIN'])
            ->setParola('hash-test');
        $this->managerEntitati->persist($administrator);
        $this->managerEntitati->flush();

        return $administrator;
    }

    private function creeazaImaginePng(): string
    {
        $cale = tempnam(sys_get_temp_dir(), 'branding-png-');
        self::assertNotFalse($cale);
        file_put_contents(
            $cale,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true),
        );

        return $cale;
    }

    /** @return list<string> */
    private function listeazaFisiereleBranding(): array
    {
        $fisiere = glob($this->directorBranding.'/*');
        if (false === $fisiere) {
            return [];
        }

        sort($fisiere);

        return array_values(array_filter($fisiere, 'is_file'));
    }

    private function stergeFisiereleDeTest(): void
    {
        foreach ($this->listeazaFisiereleBranding() as $fisier) {
            unlink($fisier);
        }
    }
}
