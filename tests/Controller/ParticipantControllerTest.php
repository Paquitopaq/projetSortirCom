<?php

namespace App\Tests\Controller;

use App\Entity\GroupePrive;
use App\Entity\Participant;
use App\Entity\Sortie;
use Doctrine\ORM\EntityManagerInterface;
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
        $form['profil[plainPassword]'] = '45'; // champ vide

        $client->submit($form);

        // Suivre la réponse (pas de redirection ici car le formulaire est invalide)
        $this->assertResponseIsSuccessful();


    }

    public function testAffichageProfilAvecFixtures(): void{
            $client = static::createClient();
            $container = static::getContainer();
            $em = $container->get(EntityManagerInterface::class);

            // Récupère le participant et la sortie depuis les fixtures
            /** @var Participant $participant */
            $participant = $em->getRepository(Participant::class)->findOneBy(['email' => 'alice@example.com']);
            /** @var Sortie $sortie */
             $sortie = $em->getRepository(Sortie::class)->findOneBy(['nom' => 'Sortie Test 1']);
            // Crée un groupe privé lié au participant et à la sortie
            $groupe = new GroupePrive();
            $groupe->setNomGroupe('Groupe lié aux fixtures');
            $groupe->setOrganisateur($participant);
            $groupe->setSortie($sortie);
            $groupe->addMembre($participant);
            $em->persist($groupe);
            $em->flush();

            // Connexion et accès à la page
            $client->loginUser($participant);
            $crawler = $client->request('GET', '/profil/' . $participant->getId());

            $this->assertResponseIsSuccessful();

            // Vérifie que le nom du participant est affiché
            $this->assertSelectorTextContains('body', $participant->getNom());

            // Vérifie que le groupe est affiché mais supprimer dans les groupes privé test donc à enlever
            //$this->assertSelectorTextContains('.activity-item h3', 'Groupe Modifié');


            // Vérifie que le compteur de groupes est correct
            $this->assertSelectorTextContains('.activity-count', '0');

            // Vérifie que les boutons "Modifier" et "Supprimer" sont présents
            $this->assertSelectorExists('.status-edit');
            $this->assertSelectorExists('form[action*="supprimer"] button');
        }






}
