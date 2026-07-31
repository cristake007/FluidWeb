<?php

namespace App\Controller;

use App\Repository\UtilizatorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdministrareUtilizatoriController extends AbstractController
{
    #[Route('/administrare/utilizatori', name: 'administrare_utilizatori', methods: ['GET'])]
    public function listeaza(UtilizatorRepository $utilizatorRepository): Response
    {
        return $this->render('administrare/utilizatori.html.twig', [
            'utilizatori' => $utilizatorRepository->gasestePentruAdministrare(),
        ]);
    }
}
