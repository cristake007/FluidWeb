<?php

namespace App\Security;

use App\Entity\Utilizator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, mixed> */
final class VotantPermisiune extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $this->estePermisiuneCunoscuta($attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $utilizator = $token->getUser();

        return $utilizator instanceof Utilizator && $this->utilizatorArePermisiune($utilizator, $attribute);
    }

    private function utilizatorArePermisiune(Utilizator $utilizator, string $permisiune): bool
    {
        return $utilizator->esteActiv() && in_array('ROLE_ADMIN', $utilizator->getRoles(), true);
    }

    private function estePermisiuneCunoscuta(string $permisiune): bool
    {
        return in_array($permisiune, Permisiuni::toate(), true);
    }
}
