<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Certification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('ROLE_USER')] // Only authenticated users can access their profile
class ProfileController extends AbstractController
{
    /**
     * Displays the user's profile page, including their certifications.
     *
     * @param EntityManagerInterface $entityManager The Doctrine entity manager for database operations
     * @return Response Renders the profile page with user information and certifications
     */
    #[Route('/', name: 'profile_show')]
    public function show(EntityManagerInterface $entityManager): Response
    {
        // Retrieve the currently authenticated user
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à votre profil.');
        }

        // Fetch certifications associated with the user
        $certifications = $entityManager->getRepository(Certification::class)->findBy([
            'user' => $user,
        ]);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'certifications' => $certifications,
        ]);
    }
}
