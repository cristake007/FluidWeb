<?php

namespace App\Twig;

use App\Interfata\ServiciuNavigatie;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ExtensieNavigatie extends AbstractExtension
{
    public function __construct(private readonly ServiciuNavigatie $serviciuNavigatie)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('navigatie_aplicatie', $this->serviciuNavigatie->obtineIntrari(...)),
        ];
    }
}
