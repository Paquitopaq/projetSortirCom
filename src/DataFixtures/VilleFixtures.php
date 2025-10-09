<?php

namespace App\DataFixtures;

use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class VilleFixtures extends Fixture
{
    public const VILLES = [
        ['idVille' => 'V001', 'nom' => 'Nantes', 'codePostal' => '44000'],
        ['idVille' => 'V002', 'nom' => 'Angers', 'codePostal' => '49000'],
        ['idVille' => 'V003', 'nom' => 'La Roche-sur-Yon', 'codePostal' => '85000'],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::VILLES as $data) {
            $ville = new Ville();
            $ville->setNom($data['nom']);
            $ville->setCodePostal($data['codePostal']);

            $manager->persist($ville);
            $this->addReference($data['idVille'], $ville); // utile pour LieuFixtures
        }

        $manager->flush();
    }
}
