<?php

namespace App\DataFixtures;

use App\Entity\Site;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SiteFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $sites = [
            ['nom' => 'Campus Nantes'],
            ['nom' => 'Campus Angers'],
            ['nom' => 'Campus La Roche-sur-Yon'],
        ];

        foreach ($sites as $data) {
            $site = new Site();
            $site->setNom($data['nom']);

            $manager->persist($site);
        }

        $manager->flush();
    }
}
