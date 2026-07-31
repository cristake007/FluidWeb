<?php

namespace App\Tests\Administrare;

use App\Entity\Utilizator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class BrandingTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $managerEntitati;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->managerEntitati = static::getContainer()->get(EntityManagerInterface::class);
        $this->managerEntitati->createQuery('DELETE FROM App\\Entity\\ConfiguratieBranding')->execute();
        $this->managerEntitati->createQuery('DELETE FROM App\\Entity\\Utilizator')->execute();
        $this->managerEntitati->clear();
    }

    public function testAccesulEstePermisNumaiAdministratorului(): void
    {
        $utilizator = $this->creeazaUtilizator('utilizator@example.com', ['ROLE_USER']);
        $this->client->loginUser($utilizator);

        $this->client->request('GET', '/administrare/branding');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);

        $this->client->restart();
        $this->client->request('GET', '/administrare/branding');
        self::assertResponseRedirects('/autentificare', Response::HTTP_FOUND);
    }

    public function testFormularulFolosesteValorileImpliciteCandNuExistaConfiguratie(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $this->client->loginUser($administrator);

        $pagina = $this->client->request('GET', '/administrare/branding');

        self::assertResponseIsSuccessful();
        self::assertRouteSame('administrare_branding');
        self::assertSelectorTextSame('h1', 'Branding');
        self::assertInputValueSame('configuratie_branding[numeAplicatie]', 'FluidWeb');
        self::assertInputValueSame('configuratie_branding[culoarePrincipala]', '#164194');
        self::assertInputValueSame('configuratie_branding[culoareSecundara]', '#D41131');
        self::assertCount(1, $pagina->filter('form[enctype="multipart/form-data"]'));
    }

    public function testAdministratorulPoateSalvaDateleSiPrimesteMesajDeSucces(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $this->client->loginUser($administrator);
        $pagina = $this->client->request('GET', '/administrare/branding');

        $formular = $pagina->selectButton('Salvează brandingul')->form([
            'configuratie_branding[numeAplicatie]' => 'Portal intern',
            'configuratie_branding[culoarePrincipala]' => '#123ABC',
            'configuratie_branding[culoareSecundara]' => '#FEDCBA',
        ]);
        $this->client->submit($formular);

        self::assertResponseRedirects('/administrare/branding', Response::HTTP_SEE_OTHER);
        $pagina = $this->client->followRedirect();
        self::assertInputValueSame('configuratie_branding[numeAplicatie]', 'Portal intern');
        self::assertInputValueSame('configuratie_branding[culoarePrincipala]', '#123ABC');
        self::assertInputValueSame('configuratie_branding[culoareSecundara]', '#FEDCBA');
        self::assertSelectorTextContains('.alert.alert-success', 'Configurația de branding a fost salvată.');
        self::assertCount(1, $pagina->filter('[data-mesaje-flash][data-turbo-temporary]'));
    }

    public function testCulorileTrebuieSaFieHexCuSaseCifre(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $this->client->loginUser($administrator);
        $pagina = $this->client->request('GET', '/administrare/branding');

        $formular = $pagina->selectButton('Salvează brandingul')->form([
            'configuratie_branding[numeAplicatie]' => 'FluidWeb',
            'configuratie_branding[culoarePrincipala]' => '164194',
            'configuratie_branding[culoareSecundara]' => '#D41',
        ]);
        $pagina = $this->client->submit($formular);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertCount(2, $pagina->filter('.invalid-feedback'));
        self::assertSelectorTextContains(
            '[id^="configuratie_branding_culoarePrincipala_error"]',
            'Introduceți o culoare hex validă, în formatul #RRGGBB.',
        );
        self::assertSelectorTextContains(
            '[id^="configuratie_branding_culoareSecundara_error"]',
            'Introduceți o culoare hex validă, în formatul #RRGGBB.',
        );
    }

    public function testUploadurileRespingFisiereleCareNuSuntImaginiValide(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $this->client->loginUser($administrator);
        $pagina = $this->client->request('GET', '/administrare/branding');
        $fisierInvalid = tempnam(sys_get_temp_dir(), 'branding-invalid-');
        self::assertNotFalse($fisierInvalid);
        file_put_contents($fisierInvalid, 'acesta nu este un fișier imagine');

        try {
            $formular = $pagina->selectButton('Salvează brandingul')->form([
                'configuratie_branding[numeAplicatie]' => 'FluidWeb',
                'configuratie_branding[culoarePrincipala]' => '#164194',
                'configuratie_branding[culoareSecundara]' => '#D41131',
            ]);
            $formular['configuratie_branding[fisierLogoPrincipal]']->upload($fisierInvalid);
            $formular['configuratie_branding[fisierLogoCompact]']->upload($fisierInvalid);
            $formular['configuratie_branding[fisierFavicon]']->upload($fisierInvalid);
            $pagina = $this->client->submit($formular);
        } finally {
            unlink($fisierInvalid);
        }

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertCount(3, $pagina->filter('.invalid-feedback'));
        self::assertSelectorTextContains(
            '[id^="configuratie_branding_fisierLogoPrincipal_error"]',
            'Încărcați o imagine PNG, JPEG sau WebP validă.',
        );
        self::assertSelectorTextContains(
            '[id^="configuratie_branding_fisierFavicon_error"]',
            'Încărcați un favicon PNG sau ICO valid.',
        );
    }

    public function testUploadurileValideSuntSalvateSiPrevizualizate(): void
    {
        $administrator = $this->creeazaUtilizator('admin@example.com', ['ROLE_ADMIN']);
        $this->client->loginUser($administrator);
        $pagina = $this->client->request('GET', '/administrare/branding');
        $imaginePng = tempnam(sys_get_temp_dir(), 'branding-png-');
        self::assertNotFalse($imaginePng);
        file_put_contents(
            $imaginePng,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true),
        );

        try {
            $formular = $pagina->selectButton('Salvează brandingul')->form([
                'configuratie_branding[numeAplicatie]' => 'FluidWeb',
                'configuratie_branding[culoarePrincipala]' => '#164194',
                'configuratie_branding[culoareSecundara]' => '#D41131',
            ]);
            $formular['configuratie_branding[fisierLogoPrincipal]']->upload($imaginePng);
            $formular['configuratie_branding[fisierLogoCompact]']->upload($imaginePng);
            $formular['configuratie_branding[fisierFavicon]']->upload($imaginePng);
            $this->client->submit($formular);
        } finally {
            unlink($imaginePng);
        }

        self::assertResponseRedirects('/administrare/branding', Response::HTTP_SEE_OTHER);
        $pagina = $this->client->followRedirect();
        self::assertCount(1, $pagina->filter('img[data-previzualizare="logo-principal"][src="/administrare/branding/fisier/logo-principal"]'));
        self::assertCount(1, $pagina->filter('img[data-previzualizare="logo-compact"][src="/administrare/branding/fisier/logo-compact"]'));
        self::assertCount(1, $pagina->filter('img[data-previzualizare="favicon"][src="/administrare/branding/fisier/favicon"]'));

        $this->client->request('GET', '/administrare/branding/fisier/logo-principal');
        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'image/png');
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
