<?php

namespace App\DataFixtures;

use App\Entity\Participant;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ParticipantFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $participants = [
            [
                'email' => 'alice@example.com',
                'roles' => ['ROLE_USER'],
                'password' => 'password',
                'pseudo' => 'alice123',
                'nom' => 'Dupont',
                'prenom' => 'Alice',
                'telephone' => '0601020304',
                'administrateur' => false,
                'actif' => true,
                'photoProfil' => null,
            ],
            [
                'email' => 'bob@example.com',
                'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
                'password' => 'password',
                'pseudo' => 'bobster',
                'nom' => 'Martin',
                'prenom' => 'Bob',
                'telephone' => '0605060708',
                'administrateur' => true,
                'actif' => true,
                'photoProfil' => null,
            ],
            [
                'email' => 'carol@example.com',
                'roles' => ['ROLE_USER'],
                'password' => 'password',
                'pseudo' => 'carolC',
                'nom' => 'Durand',
                'prenom' => 'Carol',
                'telephone' => '0611121314',
                'administrateur' => false,
                'actif' => false,
                'photoProfil' => null,
            ],
        ];

        foreach ($participants as $data) {
            $participant = new Participant();
            $participant->setEmail($data['email']);
            $participant->setRoles($data['roles']);
            $participant->setPseudo($data['pseudo']);
            $participant->setNom($data['nom']);
            $participant->setPrenom($data['prenom']);
            $participant->setTelephone($data['telephone']);
            $participant->setAdministrateur($data['administrateur']);
            $participant->setActif($data['actif']);
            $participant->setPhotoProfil($data['photoProfil']);

            $hashedPassword = $this->passwordHasher->hashPassword($participant, $data['password']);
            $participant->setPassword($hashedPassword);

            $manager->persist($participant);
        }

        $manager->flush();
    }
}
