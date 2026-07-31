<?php

namespace App\Entity;

use App\Repository\UtilizatorRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UtilizatorRepository::class)]
#[ORM\Table(name: 'utilizator')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Există deja un utilizator cu această adresă de email.')]
class Utilizator implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Completați adresa de email.')]
    #[Assert\Email(message: 'Introduceți o adresă de email validă.')]
    #[Assert\Length(max: 180, maxMessage: 'Adresa de email poate avea cel mult {{ limit }} de caractere.')]
    private string $email;

    #[ORM\Column(length: 255)]
    private string $parola;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Completați prenumele.')]
    #[Assert\Length(max: 100, maxMessage: 'Prenumele poate avea cel mult {{ limit }} de caractere.')]
    private string $prenume;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Completați numele.')]
    #[Assert\Length(max: 100, maxMessage: 'Numele poate avea cel mult {{ limit }} de caractere.')]
    private string $nume;

    /** @var list<string> */
    #[ORM\Column]
    private array $roluri = [];

    #[ORM\Column]
    private bool $activ = true;

    #[ORM\Column]
    private \DateTimeImmutable $creatLa;

    #[ORM\Column]
    private \DateTimeImmutable $actualizatLa;

    public function __construct()
    {
        $this->initializeTimestamps();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roluri, 'ROLE_USER']));
    }

    /** @param list<string> $roluri */
    public function setRoluri(array $roluri): static
    {
        $this->roluri = $roluri;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->parola;
    }

    public function setParola(string $hashParola): static
    {
        $this->parola = $hashParola;

        return $this;
    }

    public function getPrenume(): string
    {
        return $this->prenume;
    }

    public function setPrenume(string $prenume): static
    {
        $this->prenume = $prenume;

        return $this;
    }

    public function getNume(): string
    {
        return $this->nume;
    }

    public function setNume(string $nume): static
    {
        $this->nume = $nume;

        return $this;
    }

    public function esteActiv(): bool
    {
        return $this->activ;
    }

    public function setActiv(bool $activ): static
    {
        $this->activ = $activ;

        return $this;
    }

    public function getCreatLa(): \DateTimeImmutable
    {
        return $this->creatLa;
    }

    public function getActualizatLa(): \DateTimeImmutable
    {
        return $this->actualizatLa;
    }

    public function eraseCredentials(): void
    {
    }

    public function __serialize(): array
    {
        $date = (array) $this;
        $date["\0".self::class."\0parola"] = hash('crc32c', $this->parola);

        return $date;
    }

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->creatLa = $now;
        $this->actualizatLa = $now;
    }

    #[ORM\PreUpdate]
    public function updateActualizatLa(): void
    {
        $this->actualizatLa = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }
}
