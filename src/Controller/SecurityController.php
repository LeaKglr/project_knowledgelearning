<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * Handles the user login process.
     *
     * @param AuthenticationUtils $authenticationUtils Provides authentication error messages and last entered username
     * @return Response Renders the login page
     */
    #[Route(path: '/', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Uncomment this if you want to redirect logged-in users to another route
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // Retrieve the last authentication error, if any
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // Retrieve the last entered username to prefill the login form
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * Handles the user logout process.
     *
     * @throws \LogicException This method should never be executed directly
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
         // This method should never be executed. Symfony handles logout automatically via security.yaml.
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
