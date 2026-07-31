<?php

namespace App\Serviciu;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class StocareFisiereBranding implements InterfataStocareFisiereBranding
{
    public function __construct(private string $directorBranding)
    {
    }

    public function salveaza(UploadedFile $fisier, string $prefix): string
    {
        if (!is_dir($this->directorBranding) && !mkdir($this->directorBranding, 0775, true) && !is_dir($this->directorBranding)) {
            throw new \RuntimeException(sprintf('Directorul de branding "%s" nu a putut fi creat.', $this->directorBranding));
        }

        $extensie = $fisier->guessExtension();
        if (null === $extensie) {
            throw new \RuntimeException('Extensia fișierului de branding nu a putut fi determinată.');
        }

        $numeFisier = sprintf('%s-%s.%s', $prefix, bin2hex(random_bytes(16)), $extensie);
        $fisier->move($this->directorBranding, $numeFisier);

        return $numeFisier;
    }

    public function sterge(?string $numeFisier): void
    {
        if (null === $numeFisier) {
            return;
        }

        $cale = $this->cale($numeFisier);
        if (is_file($cale)) {
            unlink($cale);
        }
    }

    public function cale(string $numeFisier): string
    {
        return $this->directorBranding.'/'.basename($numeFisier);
    }
}
