<?php

namespace App\Tests;

use App\Command\CreeazaAdministratorCommand;
use App\Entity\Utilizator;
use App\Repository\UtilizatorRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreeazaAdministratorCommandTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UtilizatorRepository $utilizatorRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->utilizatorRepository = $container->get(UtilizatorRepository::class);
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE utilizator RESTART IDENTITY');
    }

    #[Test]
    public function administratorulEsteCreatPersistatSiAreParolaHashuita(): void
    {
        $parola = 'parola-administrator-cu-minim-24-caractere';
        $tester = $this->ruleazaComanda($parola, '  ADMIN@Example.COM  ');

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('admin@example.com', $tester->getDisplay());
        self::assertStringNotContainsString($parola, $tester->getDisplay());
        self::assertStringNotContainsString($parola, $tester->getErrorOutput());

        $this->entityManager->clear();
        $utilizator = $this->utilizatorRepository->findOneBy(['email' => 'admin@example.com']);
        self::assertInstanceOf(Utilizator::class, $utilizator);
        self::assertSame('admin@example.com', $utilizator->getEmail());
        self::assertContains('ROLE_ADMIN', $utilizator->getRoles());
        self::assertContains('ROLE_USER', $utilizator->getRoles());
        self::assertTrue($utilizator->esteActiv());
        self::assertNotSame($parola, $utilizator->getPassword());
        self::assertStringNotContainsString($parola, $utilizator->getPassword());
        self::assertTrue(
            static::getContainer()->get(UserPasswordHasherInterface::class)->isPasswordValid($utilizator, $parola),
        );
    }

    #[Test]
    public function parolaGoalaEsteRefuzataFaraPersistentaPartiala(): void
    {
        $tester = $this->ruleazaComanda('');

        self::assertSame(Command::INVALID, $tester->getStatusCode());
        self::assertSame(0, $this->utilizatorRepository->count([]));
    }

    #[Test]
    public function parolaPreaScurtaEsteRefuzataFaraPersistentaPartiala(): void
    {
        $tester = $this->ruleazaComanda('prea-scurta');

        self::assertSame(Command::INVALID, $tester->getStatusCode());
        self::assertSame(0, $this->utilizatorRepository->count([]));
    }

    #[Test]
    public function emailDuplicatEsteRefuzatFaraModificareaUtilizatoruluiExistent(): void
    {
        $existent = (new Utilizator())
            ->setEmail('admin@example.com')
            ->setPrenume('Existent')
            ->setNume('Administrator')
            ->setRoluri(['ROLE_ADMIN'])
            ->setActiv(true)
            ->setParola('hash-initial');
        $this->entityManager->persist($existent);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $parola = 'parola-administrator-cu-minim-24-caractere';
        $tester = $this->ruleazaComanda($parola, 'ADMIN@example.com');

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringNotContainsString($parola, $tester->getDisplay());
        self::assertStringNotContainsString($parola, $tester->getErrorOutput());
        self::assertSame(1, $this->utilizatorRepository->count([]));

        $utilizator = $this->utilizatorRepository->findOneBy(['email' => 'admin@example.com']);
        self::assertInstanceOf(Utilizator::class, $utilizator);
        self::assertSame('Existent', $utilizator->getPrenume());
        self::assertSame('Administrator', $utilizator->getNume());
        self::assertSame('hash-initial', $utilizator->getPassword());
    }

    #[Test]
    public function dateInvalideNuCreeazaUtilizatorPartial(): void
    {
        $tester = $this->ruleazaComanda('parola-administrator-cu-minim-24-caractere', 'email-invalid');

        self::assertSame(Command::INVALID, $tester->getStatusCode());
        self::assertSame(0, $this->utilizatorRepository->count([]));
    }

    private function ruleazaComanda(
        string $parola,
        string $email = 'admin@example.com',
        string $prenume = 'Cristian',
        string $nume = 'Popa',
    ): CommandTester {
        $comanda = static::getContainer()->get(CreeazaAdministratorCommand::class);
        self::assertInstanceOf(CreeazaAdministratorCommand::class, $comanda);

        $tester = new CommandTester($comanda);
        $tester->setInputs([$parola]);
        $tester->execute([
            'email' => $email,
            'prenume' => $prenume,
            'nume' => $nume,
        ], [
            'capture_stderr_separately' => true,
            'interactive' => false,
        ]);

        return $tester;
    }
}
