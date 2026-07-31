<?php

namespace App\Repository;

use App\Entity\ConfiguratieBranding;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ConfiguratieBranding> */
final class ConfiguratieBrandingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConfiguratieBranding::class);
    }

    public function gasesteConfiguratia(): ?ConfiguratieBranding
    {
        return $this->find(ConfiguratieBranding::ID_UNIC);
    }
}
