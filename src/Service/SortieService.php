<?php

namespace App\Service;

use App\Entity\Sortie;
use App\Enum\Etat;
use Doctrine\ORM\EntityManagerInterface;

class SortieService
{

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }
    public function createSortie(Sortie $sortie, bool $publier): void
    {
        if ($publier) {
            $sortie->setEtat(Etat::OPEN);
        } else {
            $sortie->setEtat(Etat::CREATED);
        }
        
        $this->entityManager->persist($sortie);
        $this->entityManager->flush();
    }

    public function publier(Sortie $sortie): void
    {

        $sortie->setEtat(Etat::OPEN);
        $this->entityManager->flush();
    }

}