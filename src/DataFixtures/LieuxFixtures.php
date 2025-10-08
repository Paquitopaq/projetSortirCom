<?php

namespace App\DataFixtures;

use App\Entity\Lieu;
use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LieuxFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $lieux = [
            ['idLieu' => 'L001', 'nom' => 'Parc du Grand Blottereau', 'rue' => 'Boulevard Auguste-Peneau', 'latitude' => 47.2184, 'longitude' => -1.5536, 'ville' => 'V001'],
            ['idLieu' => 'L002', 'nom' => 'Château d’Angers', 'rue' => 'Promenade du Bout du Monde', 'latitude' => 47.4716, 'longitude' => -0.5542, 'ville' => 'V002'],
            ['idLieu' => 'L003', 'nom' => 'Place Napoléon', 'rue' => 'Place Napoléon', 'latitude' => 46.6705, 'longitude' => -1.4264, 'ville' => 'V003'],
        ];

        foreach ($lieux as $data) {
            $lieu = new Lieu();
            $lieu->setIdLieu($data['idLieu']);
            $lieu->setNom($data['nom']);
            $lieu->setRue($data['rue']);
            $lieu->setLatitude($data['latitude']);
            $lieu->setLongitude($data['longitude']);
            $lieu->setVille($this->getReference($data['ville'], Ville::class));

            $manager->persist($lieu);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [VilleFixtures::class];
    }
}
