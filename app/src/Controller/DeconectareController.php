<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeconectareController
{
    #[Route('/deconectare', name: 'deconectare', methods: ['POST'])]
    public function deconectare(): Response
    {
        throw new \LogicException('Deconectarea trebuie sa fie interceptata de firewall-ul Symfony.');
    }
}
