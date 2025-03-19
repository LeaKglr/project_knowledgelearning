<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginControllerTest extends WebTestCase
{
    private $client;
    private ?EntityManagerInterface $entityManager = null;
    private ?UserPasswordHasherInterface $passwordHasher = null;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown(); // Ensures the kernel is properly reset before creating a new client

        // Create a new client for making HTTP requests
        $this->client = static::createClient();

        // Retrieve services from Symfony's container
        $this->entityManager = self::getContainer()->get('doctrine.orm.entity_manager'); 
        $this->passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class); 

        // Remove any existing test user to avoid conflicts
        $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'testuser@example.com']);
        if ($existingUser) {
            $this->entityManager->remove($existingUser);
            $this->entityManager->flush();
        }

        // Create a new test user
        $user = new User();
        $user->setEmail('testuser@example.com');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'TestPassword123!'));
        $user->setRoles(['ROLE_USER']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * Test if a user can log in successfully with valid credentials.
     */
    public function testUserCanLoginSuccessfully()
    {
        // Access the login page
        $crawler = $this->client->request('GET', '/');

        // Ensure the page loads successfully
        $this->assertResponseIsSuccessful();

        // Fill in and submit the login form with correct credentials
        $form = $crawler->selectButton("Se connecter")->form([
            'email' => 'testuser@example.com',
            'password' => 'TestPassword123!'
        ]);

        $this->client->submit($form);

        // Check if login redirects to the home page
        $this->assertResponseRedirects('/home');

        // Follow the redirection and check the home page content
        $this->client->followRedirect();
        $this->assertSelectorTextContains('h1', 'Knowledge Learning');
    }

    /**
     * Test if login fails with incorrect credentials.
     */
    public function testUserCannotLoginWithInvalidCredentials()
    {
        // Access the login page
        $crawler = $this->client->request('GET', '/');

        // Ensure the login page loads successfully
        $this->assertResponseIsSuccessful();

        // Fill in the login form with incorrect password
        $form = $crawler->selectButton("Se connecter")->form([
            'email' => 'testuser@example.com',
            'password' => 'MauvaisMotDePasse!'
        ]);
        $this->client->submit($form);

        // Verify the user is redirected back to login after failed attempt
        $this->assertResponseRedirects('/');

        // Follow the redirection and check for an error message
        $this->client->followRedirect();
        $this->assertSelectorTextContains('.alert.alert-danger', 'Invalid credentials.');
    }

    /**
     * Clean up resources after the test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
