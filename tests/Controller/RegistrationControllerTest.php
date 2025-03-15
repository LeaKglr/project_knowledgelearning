<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    /**
     * Test if a user can successfully register.
     */
    public function testUserCanRegisterSuccessfully()
    {
        $client = static::createClient();

        // Request the registration page
        $crawler = $client->request('GET', '/register');
        
        // Ensure the page loads successfully
        $this->assertResponseIsSuccessful();

        // Generate a unique email to avoid duplicates in the database
        $email = 'test' . uniqid() . '@example.com';

        // Fill in the registration form with valid data
        $form = $crawler->selectButton("S'inscrire")->form([
            'registration_form[email]' => $email,
            'registration_form[password]' => 'TestPassword123!',
        ]);

        // Submit the form
        $client->submit($form);

        // Check if there are form errors (optional debug)
        if ($crawler->filter('.error')->count() > 0) {
            dump($crawler->filter('.error')->text());
        }

        // Ensure the user is redirected after successful registration
        $this->assertResponseRedirects('/home');

        // Verify that the user has been saved in the database
        $entityManager = $client->getContainer()->get('doctrine.orm.entity_manager');
        $user = $entityManager->getRepository(\App\Entity\User::class)->findOneBy(['email' => $email]);

        // Check that the user exists
        $this->assertNotNull($user, 'L’utilisateur n’a pas été trouvé en base.');
    }

}
