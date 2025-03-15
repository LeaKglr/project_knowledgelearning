<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    /**
     * Constructor to inject the password hasher service.
     */
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * Load initial data into the database.
     */
    public function load(ObjectManager $manager): void
    {
        // Create a test user
        $user = new User();
        $user->setEmail('test@example.com'); // Default test email
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Test1234!')); // Secure password hashing
        $user->setRoles(['ROLE_USER']); // Assigning a basic user role
        $user->setIsVerified(true); // Simulating an already verified account for testing purposes

        $manager->persist($user);
        $manager->flush(); 
    }
}
