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
            ['nom' => 'Parc du Grand Blottereau', 'rue' => 'Boulevard Auguste-Peneau', 'latitude' => 47.2184, 'longitude' => -1.5536, 'ville' => 'V001'],
            ['nom' => 'Château d’Angers', 'rue' => 'Promenade du Bout du Monde', 'latitude' => 47.4716, 'longitude' => -0.5542, 'ville' => 'V002'],
            ['nom' => 'Place Napoléon', 'rue' => 'Place Napoléon', 'latitude' => 46.6705, 'longitude' => -1.4264, 'ville' => 'V003'],
        ];

        foreach ($lieux as $index => $data) {
            $lieu = new Lieu();
            $lieu->setNom($data['nom']);
            $lieu->setRue($data['rue']);
            $lieu->setLatitude($data['latitude']);
            $lieu->setLongitude($data['longitude']);

            // ✅ getReference() nécessite 2 arguments avec Doctrine Fixtures 2.1.0
            $ville = $this->getReference($data['ville'], Ville::class);
            $lieu->setVille($ville);

            $manager->persist($lieu);

            // ✅ ajoute une référence pour SortieFixtures
            $this->addReference('lieu_' . $index, $lieu);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [VilleFixtures::class];
    }
}
