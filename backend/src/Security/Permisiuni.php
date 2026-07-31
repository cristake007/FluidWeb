<?php

namespace App\Security;

final class Permisiuni
{
    public const UTILIZATORI_VIZUALIZEAZA = 'utilizatori.vizualizeaza';
    public const UTILIZATORI_ADMINISTREAZA = 'utilizatori.administreaza';
    public const SETARI_VIZUALIZEAZA = 'setari.vizualizeaza';
    public const SETARI_ADMINISTREAZA = 'setari.administreaza';

    /** @return list<string> */
    public static function toate(): array
    {
        return [
            self::UTILIZATORI_VIZUALIZEAZA,
            self::UTILIZATORI_ADMINISTREAZA,
            self::SETARI_VIZUALIZEAZA,
            self::SETARI_ADMINISTREAZA,
        ];
    }
}
