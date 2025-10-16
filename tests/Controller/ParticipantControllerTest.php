<?php

namespace App\Tests\Controller;

use App\Entity\Participant;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ParticipantControllerTest extends WebTestCase
{
    private function getOrCreateTestUser($entityManager): Participant
    {
        $participant = $entityManager->getRepository(Participant::class)->findOneBy(['email' => 'test@example.com']);

        if (!$participant) {
            $participant = new Participant();
            $participant->setEmail('test@example.com');
            $participant->setNom('Testeur');
            $participant->setPrenom('Test');
            $participant->setPseudo('testuser');
            $participant->setTelephone('0600000000');
            $participant->setRoles(['ROLE_USER']);

            $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
            $participant->setPassword($hasher->hashPassword($participant, 'password123'));

            $entityManager->persist($participant);
            $entityManager->flush();
        }

        return $participant;
    }

    public function testUpdateProfilWithValidData(): void
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');

        $participant = $this->getOrCreateTestUser($entityManager);
        $client->loginUser($participant);

        $crawler = $client->request('GET', '/profil/update');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form.profile-form');

        $form = $crawler->filter('form.profile-form')->form();
        $form['profil[plainPassword]'] = 'newpassword123';
        $form['selected_avatar'] = 'carcajou.jpg';

        $client->submit($form);

        // Vérifie simplement que la redirection a bien lieu
        $this->assertResponseRedirects('/');
    }


    public function testUpdateProfilWithInvalidForm(): void
    {
        $client = static::createClient();
        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');

        $participant = $this->getOrCreateTestUser($entityManager);
        $client->loginUser($participant);

        $crawler = $client->request('GET', '/profil/update');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form.profile-form');

        $form = $crawler->filter('form.profile-form')->form();
        $form['profil[plainPassword]'] = ''; // champ vide

        $client->submit($form);

        // Suivre la réponse (pas de redirection ici car le formulaire est invalide)
        $this->assertResponseIsSuccessful();


    }


}
