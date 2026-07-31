<?php

namespace App\Tests\Dublura;

use App\Serviciu\InterfataStocareFisiereBranding;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class StocareFisiereBrandingControlata implements InterfataStocareFisiereBranding
{
    private int $numarSalvare = 0;
    private ?int $numarSalvareCuEsec = null;

    public function __construct(private readonly InterfataStocareFisiereBranding $stocareReala)
    {
    }

    public function esueazaLaSalvarea(int $numarSalvare): void
    {
        $this->numarSalvare = 0;
        $this->numarSalvareCuEsec = $numarSalvare;
    }

    public function salveaza(UploadedFile $fisier, string $prefix): string
    {
        ++$this->numarSalvare;
        if ($this->numarSalvare === $this->numarSalvareCuEsec) {
            throw new \RuntimeException('Eșec de stocare simulat.');
        }

        return $this->stocareReala->salveaza($fisier, $prefix);
    }

    public function sterge(?string $numeFisier): void
    {
        $this->stocareReala->sterge($numeFisier);
    }

    public function cale(string $numeFisier): string
    {
        return $this->stocareReala->cale($numeFisier);
    }
}
