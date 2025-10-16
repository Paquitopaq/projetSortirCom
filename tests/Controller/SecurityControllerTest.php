<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testLoginPageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        // Trouve le formulaire de connexion
        $form = $crawler->selectButton('Connexion')->form();

        // Remplit avec de fausses infos
        $form['email'] = 'fake@example.com';
        $form['password'] = 'wrongpassword';

        $client->submit($form);

        // Il doit revenir sur la page de login avec un message d'erreur
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.alert', 'Message d’erreur attendu');
    }
}
