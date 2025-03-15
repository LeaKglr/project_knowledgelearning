<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class UserCrudController extends AbstractCrudController
{
    private UserPasswordHasherInterface $passwordHasher;
    private MailerInterface $mailer;
    private VerifyEmailHelperInterface $verifyEmailHelper;

    /**
     * Constructor to initialize dependencies.
     *
     * @param MailerInterface $mailer Handles sending emails.
     * @param VerifyEmailHelperInterface $verifyEmailHelper Manages email verification links.
     * @param UserPasswordHasherInterface $passwordHasher Hashes user passwords securely.
     */
    public function __construct(MailerInterface $mailer, VerifyEmailHelperInterface $verifyEmailHelper, UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
        $this->mailer = $mailer;
        $this->verifyEmailHelper = $verifyEmailHelper;
    }

    /**
     * Returns the associated entity class.
     */
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    /**
     * Handles the creation of a new user.
     *
     * @param EntityManagerInterface $entityManager The entity manager for database operations.
     * @param User $entityInstance The user entity being persisted.
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            // Set the creation date if this is a new user
            if (!$entityInstance->getId()) {
                $entityInstance->setCreatedAt(new \DateTimeImmutable());
            }

            // Hash the password only if a new one is provided
            if (!empty($entityInstance->getPassword())) {
                $entityInstance->setPassword(
                    $this->passwordHasher->hashPassword($entityInstance, $entityInstance->getPassword())
                );
            }

            $entityManager->persist($entityInstance);
            $entityManager->flush();

            // Generate an email verification link
            $signatureComponents = $this->verifyEmailHelper->generateSignature(
                'app_verify_email',
                $entityInstance->getId(),
                $entityInstance->getEmail(),
                ['id' => $entityInstance->getId()]
            );

            // Send verification email to the new user
            $email = (new Email())
                ->from(new Address('no-reply@monsite.com', 'Mon Site'))
                ->to($entityInstance->getEmail())
                ->subject('Confirmez votre email')
                ->html('<p>Bienvenue ! Cliquez sur <a href="'.$signatureComponents->getSignedUrl().'">ce lien</a> pour confirmer votre adresse e-mail.</p>');

            $this->mailer->send($email);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    /**
     * Handles updating an existing user.
     *
     * @param EntityManagerInterface $entityManager The entity manager.
     * @param User $entityInstance The user entity being updated.
     */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof User) {
            // Check if the password has changed before hashing it again
            $originalUser = $entityManager->getUnitOfWork()->getOriginalEntityData($entityInstance);
            if (!empty($entityInstance->getPassword()) && $originalUser['password'] !== $entityInstance->getPassword()) {
                $entityInstance->setPassword(
                    $this->passwordHasher->hashPassword($entityInstance, $entityInstance->getPassword())
                );
            }

            // Update the modification date
            $entityInstance->setUpdatedAt(new \DateTimeImmutable());
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * Configures the fields displayed in the admin panel.
     *
     * @param string $pageName The current page name.
     * @return iterable List of fields to be displayed.
     */
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),

            EmailField::new('email', 'Email'),

            ArrayField::new('roles', 'Rôles'),

            TextField::new('password', 'Mot de passe')
                ->setFormTypeOption('attr', ['autocomplete' => 'new-password'])
                ->setRequired($pageName === 'new') // Required only when creating a new user
                ->onlyOnForms(), // Hide password from index view

            DateTimeField::new('createdAt', 'Créé le')
                ->hideOnForm(), // Auto-generated on creation

            DateTimeField::new('updatedAt', 'Mis à jour le')
                ->hideOnForm(), // Auto-generated on modification

            AssociationField::new('purchasedLessons', 'Leçons achetées')->hideOnForm(),
            AssociationField::new('certifications', 'Certifications obtenues')->hideOnForm(),
            AssociationField::new('lessonValidations', 'Leçons validées')->hideOnForm(),
        ];
    }
}


