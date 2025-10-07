<?php

namespace App\DataFixtures;

use App\Enum\Etat;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class EtatFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $etats = [
            ['idEtat' => 'CREATION', 'libelle' => 'Création'],
            ['idEtat' => 'OUVERTE', 'libelle' => 'Ouverte'],
            ['idEtat' => 'CLOTUREE', 'libelle' => 'Clôturée'],
            ['idEtat' => 'EN_COURS', 'libelle' => 'Activité en cours'],
            ['idEtat' => 'TERMINEE', 'libelle' => 'Activité terminée'],
            ['idEtat' => 'HISTORISEE', 'libelle' => 'Activité historisée'],
            ['idEtat' => 'ANNULEE', 'libelle' => 'Annulée'],
        ];

        foreach ($etats as $data) {
            $etat = new Etat();
            $etat->setIdEtat($data['idEtat']);
            $etat->setLibelle($data['libelle']);
            $manager->persist($etat);
        }

        $manager->flush();
    }
}
