<?php

namespace App\DataFixtures;

use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Enum\Etat;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SortieFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $sortie = new Sortie();

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

            // 🔹 Associer un lieu existant depuis LieuxFixtures
            // Doctrine 2.1.0 → getReference($name, $class)
            $randomLieuIndex = rand(0, 2); // si tu as 3 lieux
            $lieu = $this->getReference('lieu_' . $randomLieuIndex, Lieu::class);
            $sortie->setLieu($lieu);

            $manager->persist($sortie);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        // Pour que les Lieux soient chargés avant les Sorties
        return [LieuxFixtures::class];
    }

}
