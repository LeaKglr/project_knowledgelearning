<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifier
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Sends an email confirmation link to the user.
     * 
     * @param string $verifyEmailRouteName The route name for verification.
     * @param User $user The user who will receive the email.
     * @param TemplatedEmail $email The email template.
     */
    public function sendEmailConfirmation(string $verifyEmailRouteName, User $user, TemplatedEmail $email): void
    {
        // Generate a signed URL for email verification
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            $verifyEmailRouteName,
            (string) $user->getId(),
            (string) $user->getEmail(),
            ['id' => $user->getId()]
        );

        // Add verification details to the email context
        $context = $email->getContext();
        $context['signedUrl'] = $signatureComponents->getSignedUrl();
        $context['expiresAtMessageKey'] = $signatureComponents->getExpirationMessageKey();
        $context['expiresAtMessageData'] = $signatureComponents->getExpirationMessageData();

        // Set the modified context
        $email->context($context);

        // Send the verification email
        $this->mailer->send($email);
    }

    /**
     * Validates the email confirmation link and activates the user account.
     * 
     * @param Request $request The HTTP request containing the verification data.
     * @param User $user The user to verify.
     * 
     * @throws VerifyEmailExceptionInterface If verification fails.
     */
    public function handleEmailConfirmation(Request $request, User $user): void
    {
        // Validate the signed URL
        $this->verifyEmailHelper->validateEmailConfirmationFromRequest($request, (string) $user->getId(), (string) $user->getEmail());

        // Mark user as verified
        $user->setIsVerified(true);

        // Save changes to the database
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
