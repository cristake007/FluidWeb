<?php

namespace App\Serviciu;

use Symfony\Component\HttpFoundation\File\UploadedFile;

interface InterfataStocareFisiereBranding
{
    public function salveaza(UploadedFile $fisier, string $prefix): string;

    public function sterge(?string $numeFisier): void;

    public function cale(string $numeFisier): string;
}
