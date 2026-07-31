<?php

namespace App\Controller;

use App\Entity\Utilizator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PaginaPrincipalaController extends AbstractController
{
    #[Route('/', name: 'pagina_principala', methods: ['GET'])]
    public function paginaPrincipala(): Response
    {
        $utilizator = $this->getUser();

        if (!$utilizator instanceof Utilizator) {
            throw new \LogicException('Pagina principala necesita un utilizator autentificat.');
        }

        return $this->render('pagina_principala/index.html.twig', [
            'utilizator' => $utilizator,
        ]);
    }
}
