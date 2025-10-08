<?php

namespace App\DataFixtures;

use App\Entity\Sortie;
use App\Enum\Etat;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SortieFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $sortie = new Sortie();

            // Identifiant unique
            $sortie->setIdSortie('SORTIE-' . str_pad($i, 3, '0', STR_PAD_LEFT));

            // Nom
            $sortie->setNom('Sortie Test ' . $i);

            // Date de début (entre demain et 30 jours)
            $dateDebut = new \DateTime();
            $dateDebut->modify('+' . rand(1, 30) . ' days');
            $sortie->setDateHeureDebut($dateDebut);

            // Durée (entre 1 et 5 heures)
            $sortie->setDuree(rand(1, 5));

            // Date limite d'inscription (au moins 1 jour avant le début)
            $dateLimite = clone $dateDebut;
            $dateLimite->modify('-' . rand(1, 5) . ' days');
            $sortie->setDateLimiteInscription($dateLimite);

            // Nombre max d'inscriptions (5 à 20)
            $sortie->setNbInscriptionMax(rand(5, 20));

            // Info sortie
            $sortie->setInfoSortie('Description de la sortie ' . $i);

            // Etat aléatoire
            $etats = Etat::cases();
            $sortie->setEtat($etats[array_rand($etats)]);

            $manager->persist($sortie);
        }

        $manager->flush();
    }
}
