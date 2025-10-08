<?php

namespace App\Service;

use App\Entity\Participant;
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

    public function inscrireParticipant(Sortie $sortie, Participant $participant): bool {

        // TODO Ajouter des messages d'erreurs clair
        if ($sortie->getEtat() !== Etat::OPEN) {
            return false;
        }

        if ($sortie->getParticipants()->contains($participant)) {
            return false;
        }

        if ($sortie->getNbInscrits()>= $sortie->getNbInscriptionMax()) {
            return false;
        }

        $sortie->addParticipant($participant);
        $this->entityManager->persist($sortie);
        $this->entityManager->flush();

        return true;
    }

    public function removeParticipant(Sortie $sortie, Participant $participant): void {
        $sortie->removeParticipant($participant);
    }

}