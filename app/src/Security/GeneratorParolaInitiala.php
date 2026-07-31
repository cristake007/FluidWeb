<?php

namespace App\Security;

final class GeneratorParolaInitiala
{
    public function genereaza(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    }
}
