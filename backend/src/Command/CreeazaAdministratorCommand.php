<?php

namespace App\Command;

use App\Entity\Utilizator;
use App\Repository\UtilizatorRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:creeaza-administrator',
    description: 'Creeaza un utilizator administrator din parola citita din STDIN.',
)]
final class CreeazaAdministratorCommand extends Command
{
    public function __construct(
        private readonly UtilizatorRepository $utilizatorRepository,
        private readonly UserPasswordHasherInterface $hasherParola,
        private readonly EntityManagerInterface $managerEntitati,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Emailul administratorului.')
            ->addArgument('prenume', InputArgument::REQUIRED, 'Prenumele administratorului.')
            ->addArgument('nume', InputArgument::REQUIRED, 'Numele administratorului.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (string) $input->getArgument('email');
        $prenume = trim((string) $input->getArgument('prenume'));
        $nume = trim((string) $input->getArgument('nume'));

        if (!$this->dateUtilizatorValide($email, $prenume, $nume)) {
            $output->writeln('<error>Datele administratorului sunt invalide.</error>');

            return Command::INVALID;
        }

        $parola = $this->citesteParola($input);
        if (null === $parola || '' === $parola || mb_strlen($parola) < 24) {
            $output->writeln('<error>Parola administratorului este invalida.</error>');

            return Command::INVALID;
        }

        $utilizator = (new Utilizator())
            ->setEmail($email)
            ->setPrenume($prenume)
            ->setNume($nume)
            ->setRoluri(['ROLE_ADMIN'])
            ->setActiv(true);

        if (null !== $this->utilizatorRepository->findOneBy(['email' => $utilizator->getEmail()])) {
            $output->writeln('<error>Exista deja un utilizator cu acest email.</error>');

            return Command::FAILURE;
        }

        $utilizator->setParola($this->hasherParola->hashPassword($utilizator, $parola));
        unset($parola);

        try {
            $this->managerEntitati->persist($utilizator);
            $this->managerEntitati->flush();
        } catch (UniqueConstraintViolationException) {
            $output->writeln('<error>Exista deja un utilizator cu acest email.</error>');

            return Command::FAILURE;
        }

        $output->writeln(sprintf('Administratorul %s a fost creat.', $utilizator->getEmail()));

        return Command::SUCCESS;
    }

    private function dateUtilizatorValide(string $email, string $prenume, string $nume): bool
    {
        return mb_strlen(trim($email)) <= 180
            && false !== filter_var(trim($email), FILTER_VALIDATE_EMAIL)
            && '' !== $prenume
            && mb_strlen($prenume) <= 100
            && '' !== $nume
            && mb_strlen($nume) <= 100;
    }

    private function citesteParola(InputInterface $input): ?string
    {
        if (!$input instanceof StreamableInputInterface || !is_resource($stream = $input->getStream())) {
            return null;
        }

        $line = fgets($stream);
        if (false === $line) {
            return null;
        }

        if (str_ends_with($line, "\r\n")) {
            return substr($line, 0, -2);
        }

        if (str_ends_with($line, "\n") || str_ends_with($line, "\r")) {
            return substr($line, 0, -1);
        }

        return $line;
    }
}
