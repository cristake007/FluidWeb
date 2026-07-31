<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdministrareController extends AbstractController
{
    #[Route('/administrare', name: 'administrare', methods: ['GET'])]
    public function afiseaza(): Response
    {
        return $this->render('administrare/index.html.twig');
    }
}
