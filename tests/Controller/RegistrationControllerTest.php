<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    public function testRegistrationPageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        // Vérifie que la page renvoie bien un code 200
        $this->assertResponseIsSuccessful();

        // Vérifie le titre du formulaire
        $this->assertSelectorTextContains('h1.auth-title', 'Créer un compte');
    }

    public function testRegisterUserWithValidData(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        // Sélectionne le formulaire par le bouton "Créer mon compte"
        $form = $crawler->selectButton('Créer mon compte')->form();

        // Remplit les champs du formulaire
        $form['registration_form[email]'] = 'testuser@example.com';
        $form['registration_form[plainPassword]'] = 'password123';
        $form['registration_form[agreeTerms]']->tick();

        // Soumet le formulaire
        $client->submit($form);

        // Vérifie la redirection vers la page d'accueil
        $this->assertResponseRedirects('/');

        // Suit la redirection
        $client->followRedirect();

        // Vérifie que la page d’accueil se charge correctement
        $this->assertResponseIsSuccessful();
    }

    public function testRegisterUserWithInvalidData(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register');

        $form = $crawler->selectButton('Créer mon compte')->form();

        // Mot de passe trop court + pas de case cochée
        $form['registration_form[email]'] = 'invalid@example.com';
        $form['registration_form[plainPassword]'] = '123';

        $client->submit($form);

        // Le formulaire doit être redisplayé (pas de redirection)
        $this->assertResponseIsSuccessful();

        // Vérifie que le message d’erreur du mot de passe apparaît
        $this->assertSelectorTextContains('small', 'Your password should be at least');
    }
}
