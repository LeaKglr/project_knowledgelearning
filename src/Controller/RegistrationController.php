<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class RegistrationController extends AbstractController
{
    /**
     * Constructor to inject the EmailVerifier service.
     *
     * @param EmailVerifier $emailVerifier Handles email verification process
     */
    public function __construct(private EmailVerifier $emailVerifier)
    {
        $this->emailVerifier = $emailVerifier;
    }

    /**
     * Handles user registration and sends a verification email.
     *
     * @param Request $request The HTTP request object
     * @param UserPasswordHasherInterface $userPasswordHasher Password hashing service
     * @param EntityManagerInterface $entityManager Entity manager for database interactions
     * @return Response Renders the registration form or redirects on success
     */
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Retrieve and hash the password
            /** @var string $password */
            $password = $form->get('password')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $password));

            // Assign default role if none provided
            if (empty($user->getRoles())) {
                $user->setRoles(['ROLE_USER']);
            }
            
            // Save user in database
            $entityManager->persist($user);
            $entityManager->flush();

            // Send email confirmation with a signed URL
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('mailer@your-domain.com', 'Acme Mail Bot'))
                    ->to((string) $user->getEmail())
                    ->subject('Merci de confirmer votre e-mail.')
                    ->htmlTemplate('registration/email_confirmation.html.twig')
                    ->context([
                        'userId' => $user->getId(), // Pass user ID to Twig
                        'signedUrl' => $this->generateUrl('app_verify_email', [
                            'id' => $user->getId()
                        ], UrlGeneratorInterface::ABSOLUTE_URL)
                    ])
            );

            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    /**
     * Handles email verification.
     *
     * @param Request $request The HTTP request object
     * @param EntityManagerInterface $entityManager Entity manager for fetching user data
     * @return Response Redirects to home if verification fails, otherwise confirms the email
     */
    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(
        Request $request, 
        EntityManagerInterface $entityManager
    ): Response {
        // Get user ID from query parameters
        $userId = $request->query->get('id');

        if (!$userId) {
            $this->addFlash('error', 'ID utilisateur manquant.');
            return $this->redirectToRoute('app_home');
        }

        // Retrieve the user from database
        $user = $entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/confirmation_email.html.twig', [
            'userId' => $user->getId()
        ]);
    }

    /**
     * Confirms the user's email and activates their account.
     *
     * @param Request $request The HTTP request object
     * @param UserRepository $userRepository Repository to fetch user data
     * @param EntityManagerInterface $entityManager Entity manager for updating user data
     * @return Response Redirects based on email confirmation result
     */
    #[Route('/confirm/email', name: 'app_confirm_email', methods: ['POST'])]
    public function confirmEmail(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response 
    {
        // Get user ID from request
        $userId = $request->request->get('userId');
        
        if (!$userId) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_home');
        }

        // Fetch user from database
        $user = $userRepository->find($userId);
        
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_home');
        }

        // Check if the user is already verified
        if ($user->isVerified()) {
            $this->addFlash('info', 'Votre compte est déjà vérifié.');
            return $this->redirectToRoute('app_home');
        }

        // Mark the user as verified and save changes
        $user->setIsVerified(true);
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Votre compte a été activé ! Connectez-vous pour accéder à votre espace.');
        return $this->redirectToRoute('app_login');
    }
}
