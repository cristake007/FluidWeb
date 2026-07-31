<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class AutentificareController extends AbstractController
{
    #[Route('/autentificare', name: 'autentificare', methods: ['GET', 'POST'])]
    public function autentificare(Request $cerere, AuthenticationUtils $utilitareAutentificare): Response
    {
        if ($cerere->isMethod('GET') && null !== $this->getUser()) {
            return $this->redirectToRoute('pagina_principala');
        }

        return $this->render('securitate/autentificare.html.twig', [
            'ultima_adresa_email' => $utilitareAutentificare->getLastUsername(),
            'eroare_autentificare' => $utilitareAutentificare->getLastAuthenticationError(),
        ]);
    }
}
