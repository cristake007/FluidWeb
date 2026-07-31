<?php

namespace App\Controller;

use App\Entity\ConfiguratieBranding;
use App\Form\ConfiguratieBrandingType;
use App\Repository\ConfiguratieBrandingRepository;
use App\Serviciu\InterfataStocareFisiereBranding;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdministrareBrandingController extends AbstractController
{
    #[Route('/administrare/branding', name: 'administrare_branding', methods: ['GET', 'POST'])]
    public function editeaza(
        Request $request,
        ConfiguratieBrandingRepository $configuratiiBranding,
        EntityManagerInterface $managerEntitati,
        InterfataStocareFisiereBranding $stocareFisiere,
    ): Response {
        $configuratie = $configuratiiBranding->gasesteConfiguratia() ?? new ConfiguratieBranding();
        $formular = $this->createForm(ConfiguratieBrandingType::class, $configuratie, [
            'action' => $this->generateUrl('administrare_branding'),
        ]);
        $formular->handleRequest($request);

        if ($formular->isSubmitted() && $formular->isValid()) {
            $logoPrincipalVechi = $configuratie->getLogoPrincipal();
            $logoCompactVechi = $configuratie->getLogoCompact();
            $faviconVechi = $configuratie->getFavicon();
            $fisiereNoi = [];

            try {
                $fisierLogoPrincipal = $formular->get('fisierLogoPrincipal')->getData();
                if ($fisierLogoPrincipal instanceof UploadedFile) {
                    $logoPrincipalNou = $stocareFisiere->salveaza($fisierLogoPrincipal, 'logo-principal');
                    $fisiereNoi[] = $logoPrincipalNou;
                    $configuratie->setLogoPrincipal($logoPrincipalNou);
                }

                $fisierLogoCompact = $formular->get('fisierLogoCompact')->getData();
                if ($fisierLogoCompact instanceof UploadedFile) {
                    $logoCompactNou = $stocareFisiere->salveaza($fisierLogoCompact, 'logo-compact');
                    $fisiereNoi[] = $logoCompactNou;
                    $configuratie->setLogoCompact($logoCompactNou);
                }

                $fisierFavicon = $formular->get('fisierFavicon')->getData();
                if ($fisierFavicon instanceof UploadedFile) {
                    $faviconNou = $stocareFisiere->salveaza($fisierFavicon, 'favicon');
                    $fisiereNoi[] = $faviconNou;
                    $configuratie->setFavicon($faviconNou);
                }

                $managerEntitati->persist($configuratie);
                $managerEntitati->flush();
            } catch (\Throwable $exceptie) {
                foreach ($fisiereNoi as $fisierNou) {
                    try {
                        $stocareFisiere->sterge($fisierNou);
                    } catch (\Throwable) {
                    }
                }

                throw $exceptie;
            }

            if ($logoPrincipalVechi !== $configuratie->getLogoPrincipal()) {
                $stocareFisiere->sterge($logoPrincipalVechi);
            }
            if ($logoCompactVechi !== $configuratie->getLogoCompact()) {
                $stocareFisiere->sterge($logoCompactVechi);
            }
            if ($faviconVechi !== $configuratie->getFavicon()) {
                $stocareFisiere->sterge($faviconVechi);
            }
            $this->addFlash('success', 'Configurația de branding a fost salvată.');

            return $this->redirectToRoute('administrare_branding', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('administrare/branding.html.twig', [
            'formular' => $formular,
            'configuratie' => $configuratie,
        ]);
    }

    #[Route(
        '/administrare/branding/fisier/{tip}',
        name: 'administrare_branding_fisier',
        requirements: ['tip' => 'logo-principal|logo-compact|favicon'],
        methods: ['GET'],
    )]
    public function afiseazaFisier(
        string $tip,
        ConfiguratieBrandingRepository $configuratiiBranding,
        InterfataStocareFisiereBranding $stocareFisiere,
    ): BinaryFileResponse {
        $configuratie = $configuratiiBranding->gasesteConfiguratia();
        $numeFisier = match ($tip) {
            'logo-principal' => $configuratie?->getLogoPrincipal(),
            'logo-compact' => $configuratie?->getLogoCompact(),
            'favicon' => $configuratie?->getFavicon(),
            default => null,
        };

        if (null === $numeFisier || !is_file($stocareFisiere->cale($numeFisier))) {
            throw $this->createNotFoundException('Fișierul de branding nu există.');
        }

        $fisier = new File($stocareFisiere->cale($numeFisier));
        $raspuns = new BinaryFileResponse($fisier);
        $raspuns->headers->set('Content-Type', $fisier->getMimeType() ?? 'application/octet-stream');
        $raspuns->headers->set('X-Content-Type-Options', 'nosniff');
        $raspuns->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, basename($numeFisier));
        $raspuns->setPrivate();

        return $raspuns;
    }
}
