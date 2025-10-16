<?php

namespace App\Tests\Controller;

use App\Controller\GroupePriveController;
use App\Entity\GroupePrive;
use App\Entity\Participant;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GroupePriveControllerTest extends WebTestCase
{

    public function testIndex()
    {
        $client = static::createClient();
        $em = self::getContainer()->get('doctrine')->getManager();

        $participant = $em->getRepository(Participant::class)->findOneBy(['email' => 'alice@example.com']);
        $client->loginUser($participant);

        $crawler = $client->request('GET', '/mes-groupes-prives');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.activity-card');

    }

    public function testEdit()
    {
        $client = static::createClient();
        $em = self::getContainer()->get('doctrine')->getManager();

        $participant = $em->getRepository(Participant::class)->findOneBy(['email' => 'alice@example.com']);
        $client->loginUser($participant);

        $groupe = $em->getRepository(GroupePrive::class)->findOneBy(['organisateur' => $participant]);

        $crawler = $client->request('GET', '/mes-groupes-prives/' . $groupe->getId() . '/modifier');

        $form = $crawler->selectButton('Mettre à jour')->form([
            'groupe_prive[nomGroupe]' => 'Groupe Modifié',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/mes-groupes-prives');

        $client->followRedirect();
        $this->assertSelectorTextContains('.activity-item h3', 'Groupe Modifié');

    }

    public function testNew()
    {
        $client = static::createClient();
        $em = self::getContainer()->get('doctrine')->getManager();

        $participant = $em->getRepository(Participant::class)->findOneBy(['email' => 'alice@example.com']);
        $client->loginUser($participant);

        $crawler = $client->request('GET', '/mes-groupes-prives/nouveau');

        $form = $crawler->selectButton('Créer')->form([
            'groupe_prive[nomGroupe]' => 'Groupe Test',
            // ajoute les membres si nécessaire
        ]);

        $client->submit($form);
        $this->assertResponseRedirects('/mes-groupes-prives');

        $client->followRedirect();
        $this->assertSelectorTextContains('.activity-item h3', 'Groupe Modifié');

    }

    public function testDelete()
    {
        $client = static::createClient();
        $em = self::getContainer()->get('doctrine')->getManager();

        // Récupère un participant existant
        $participant = $em->getRepository(Participant::class)->findOneBy(['email' => 'alice@example.com']);
        $client->loginUser($participant);

        // Récupère un groupe appartenant à ce participant
        $groupe = $em->getRepository(GroupePrive::class)->findOneBy(['organisateur' => $participant]);

        // ⚠️ Requête GET pour initialiser la session et charger le formulaire
        $crawler = $client->request('GET', '/mes-groupes-prives');

        // Récupère le token CSRF depuis le formulaire HTML
        $form = $crawler->filter('form[action$="/' . $groupe->getId() . '/supprimer"]')->form();
        $token = $form->get('_token')->getValue();

        // Envoie la requête POST avec le token récupéré
        $client->request('POST', '/mes-groupes-prives/' . $groupe->getId() . '/supprimer', [
            '_token' => $token,
        ]);

        $this->assertResponseRedirects('/mes-groupes-prives');
        $client->followRedirect();

        // Vérifie que le groupe n’est plus affiché
        $this->assertSelectorTextNotContains('.activity-item h3', $groupe->getNomGroupe());
    }
}
