<?php

namespace App\Entity;

use App\Repository\ConfiguratieBrandingRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConfiguratieBrandingRepository::class)]
#[ORM\Table(name: 'configuratie_branding')]
class ConfiguratieBranding
{
    public const ID_UNIC = 1;
    public const CULOARE_PRINCIPALA_IMPLICITA = '#164194';
    public const CULOARE_SECUNDARA_IMPLICITA = '#D41131';

    #[ORM\Id]
    #[ORM\Column]
    private int $id = self::ID_UNIC;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Completați numele aplicației.')]
    #[Assert\Length(max: 100, maxMessage: 'Numele aplicației poate avea cel mult {{ limit }} de caractere.')]
    private string $numeAplicatie = 'FluidWeb';

    #[ORM\Column(length: 7)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Introduceți o culoare hex validă, în formatul #RRGGBB.')]
    private string $culoarePrincipala = self::CULOARE_PRINCIPALA_IMPLICITA;

    #[ORM\Column(length: 7)]
    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Introduceți o culoare hex validă, în formatul #RRGGBB.')]
    private string $culoareSecundara = self::CULOARE_SECUNDARA_IMPLICITA;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoPrincipal = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoCompact = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $favicon = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function getNumeAplicatie(): string
    {
        return $this->numeAplicatie;
    }

    public function setNumeAplicatie(string $numeAplicatie): static
    {
        $this->numeAplicatie = trim($numeAplicatie);

        return $this;
    }

    public function getCuloarePrincipala(): string
    {
        return $this->culoarePrincipala;
    }

    public function setCuloarePrincipala(string $culoarePrincipala): static
    {
        $this->culoarePrincipala = $culoarePrincipala;

        return $this;
    }

    public function getCuloareSecundara(): string
    {
        return $this->culoareSecundara;
    }

    public function setCuloareSecundara(string $culoareSecundara): static
    {
        $this->culoareSecundara = $culoareSecundara;

        return $this;
    }

    public function getLogoPrincipal(): ?string
    {
        return $this->logoPrincipal;
    }

    public function setLogoPrincipal(?string $logoPrincipal): static
    {
        $this->logoPrincipal = $logoPrincipal;

        return $this;
    }

    public function getLogoCompact(): ?string
    {
        return $this->logoCompact;
    }

    public function setLogoCompact(?string $logoCompact): static
    {
        $this->logoCompact = $logoCompact;

        return $this;
    }

    public function getFavicon(): ?string
    {
        return $this->favicon;
    }

    public function setFavicon(?string $favicon): static
    {
        $this->favicon = $favicon;

        return $this;
    }
}
