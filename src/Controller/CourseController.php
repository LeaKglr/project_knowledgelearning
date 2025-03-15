<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Order;
use App\Entity\Course;
use App\Entity\OrderDetail;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\StripeService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CourseController extends AbstractController
{
   
    /**
     * Displays the list of courses.
     *
     * @return Response The rendered course list template.
     */
    #[Route('/course', name: 'app_course')]
    public function index(): Response
    {
        return $this->render('course/index.html.twig', [
            'controller_name' => 'CourseController',
        ]);
    }

    /**
     * Handles the purchase of a course.
     *
     * @param Course $course The course to be purchased.
     * @param EntityManagerInterface $entityManager The Doctrine entity manager.
     * @return Response Redirects the user to Stripe for payment.
     */
    #[Route('/course/{id}/buy', name: 'course_buy')]
    #[IsGranted('ROLE_USER')] // Only authenticated users can purchase a course
    public function buyCourse(Course $course, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Ensure the user has verified their email before making a purchase
        if (!$user->isVerified()) {
            $this->addFlash('error', 'Vous devez vérifier votre email avant de pouvoir acheter un cursus.');
            return $this->redirectToRoute('app_home'); 
        }

        // Check if the user has already purchased this course
        $existingOrder = $entityManager->getRepository(Order::class)->findOneBy([
            'user' => $user,
            'status' => 'paid',
            'course' => $course,
        ]);

        if ($existingOrder) {
            $this->addFlash('info', 'Vous avez déjà acheté ce cursus.');
            return $this->redirectToRoute('theme_show', ['name' => $course->getTheme()->getName()]);
        }

        $this->addFlash('info', 'Redirection vers Stripe...');
        return $this->redirectToRoute('stripe_checkout_course', ['id' => $course->getId()]);
    }
}
