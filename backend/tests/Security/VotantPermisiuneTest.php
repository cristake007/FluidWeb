<?php

namespace App\Tests\Security;

use App\Entity\Utilizator;
use App\Security\Permisiuni;
use App\Security\VotantPermisiune;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class VotantPermisiuneTest extends TestCase
{
    #[Test]
    public function administratorulPrimesteFiecarePermisiuneCunoscuta(): void
    {
        $votant = new VotantPermisiune();
        $token = $this->creeazaToken(['ROLE_ADMIN']);

        foreach (Permisiuni::toate() as $permisiune) {
            self::assertSame(VoterInterface::ACCESS_GRANTED, $votant->vote($token, null, [$permisiune]));
        }
    }

    #[Test]
    public function utilizatorulObisnuitNuPrimestePermisiuniAdministrative(): void
    {
        $votant = new VotantPermisiune();
        $token = $this->creeazaToken(['ROLE_USER']);

        foreach (Permisiuni::toate() as $permisiune) {
            self::assertSame(VoterInterface::ACCESS_DENIED, $votant->vote($token, null, [$permisiune]));
        }
    }

    #[Test]
    public function utilizatorulInactivNuPrimestePermisiuni(): void
    {
        $votant = new VotantPermisiune();
        $token = $this->creeazaToken(['ROLE_ADMIN'], false);

        self::assertSame(VoterInterface::ACCESS_DENIED, $votant->vote($token, null, [Permisiuni::UTILIZATORI_VIZUALIZEAZA]));
    }

    #[Test]
    public function permisiunileNecunoscuteNuSuntAcordate(): void
    {
        $votant = new VotantPermisiune();

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $votant->vote($this->creeazaToken(['ROLE_ADMIN']), null, ['permisiune.necunoscuta']));
    }

    #[Test]
    public function votantulSeAbtinePentruAtributeDinAfaraSistemului(): void
    {
        $votant = new VotantPermisiune();

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $votant->vote(new NullToken(), null, ['ROLE_ADMIN']));
    }

    #[Test]
    public function toatePermisiunileSuntCompleteSiFaraDuplicate(): void
    {
        $permisiuni = Permisiuni::toate();

        self::assertSame([
            Permisiuni::UTILIZATORI_VIZUALIZEAZA,
            Permisiuni::UTILIZATORI_ADMINISTREAZA,
            Permisiuni::SETARI_VIZUALIZEAZA,
            Permisiuni::SETARI_ADMINISTREAZA,
        ], $permisiuni);
        self::assertSame($permisiuni, array_values(array_unique($permisiuni)));
    }

    /** @param list<string> $roluri */
    private function creeazaToken(array $roluri, bool $activ = true): UsernamePasswordToken
    {
        $utilizator = (new Utilizator())
            ->setEmail('utilizator@example.com')
            ->setPrenume('Utilizator')
            ->setNume('Test')
            ->setRoluri($roluri)
            ->setActiv($activ);

        return new UsernamePasswordToken($utilizator, 'main', $utilizator->getRoles());
    }
}
