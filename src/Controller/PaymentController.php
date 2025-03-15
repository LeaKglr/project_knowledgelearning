<?php

namespace App\Controller;

use App\Entity\Lesson;
use App\Entity\User;
use App\Entity\Course;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Order;
use App\Entity\OrderDetail;

class PaymentController extends AbstractController
{
    private StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Initiates the purchase process for a lesson using Stripe.
     *
     * @param Lesson $lesson The lesson to be purchased
     * @param SessionInterface $session The session object
     * @return Response Redirects to the Stripe checkout session
     */
    #[Route('/lesson/{id}/buy', name: 'lesson_buy')]
    #[IsGranted('ROLE_USER')] // Only authenticated users can buy lessons
    public function buyLesson(Lesson $lesson, SessionInterface $session): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour acheter une leçon.');
        }

        // Create a Stripe checkout session
        $checkoutSession = $this->stripeService->createCheckoutSession($lesson, $user->getEmail());
        
        $response = new RedirectResponse($checkoutSession->url);

        return $response;
    }

    /**
     * Handles successful payment for a lesson.
     *
     * @param int $lessonId The ID of the purchased lesson
     * @param EntityManagerInterface $entityManager The entity manager
     * @return Response Redirects to the purchased lesson
     */
    #[Route('/payment/success/{lessonId}', name: 'payment_success')]
    public function success(int $lessonId, EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour acheter une leçon.');
        }

        $lesson = $entityManager->getRepository(Lesson::class)->find($lessonId);
        if (!$lesson) {
            throw $this->createNotFoundException('Leçon introuvable.');
        }

        $course = $lesson->getCourse();
        if (!$course) {
            throw new \Exception('Erreur : La leçon n’est pas associée à un cursus.');
        }

        // Create a new order
        $order = new Order();
        $order->setUser($user);
        $order->setTotalPrice($lesson->getPrice());
        $order->setStatus('paid'); 
        $order->setCreatedAt(new \DateTime());
        $order->setUpdatedAt(new \DateTime());

        $entityManager->persist($order);

        // Add lesson details to the order
        $orderDetail = new OrderDetail();
        $orderDetail->setOrder($order);
        $orderDetail->setLesson($lesson);
        $orderDetail->setPrice($lesson->getPrice());
        $orderDetail->setCourse($lesson->getCourse());
        $orderDetail->setCreatedAt(new \DateTime());

        $entityManager->persist($orderDetail);

        // Save everything to the database
        $entityManager->flush();

        $this->addFlash('success', 'Achat réussi ! Vous avez maintenant accès à la leçon.');

        return $this->redirectToRoute('lesson_show', ['id' => $lessonId]);
    }

    /**
     * Generates a Stripe checkout session for a lesson.
     *
     * @param Lesson $lesson The lesson to be purchased
     * @param StripeService $stripeService The Stripe service
     * @return JsonResponse Returns the Stripe session ID
     */
    #[Route('/stripe/checkout/{id}', name: 'stripe_checkout', methods: ['GET'])]
    public function createCheckoutSession(Lesson $lesson, StripeService $stripeService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non connecté'], Response::HTTP_UNAUTHORIZED);
        }

        $checkoutSession = $stripeService->createCheckoutSession($lesson, $user->getEmail());

        return new JsonResponse(['id' => $checkoutSession->id]);
    }

    /**
     * Handles successful payment for an entire course.
     *
     * @param int $courseId The ID of the purchased course
     * @param EntityManagerInterface $entityManager The entity manager
     * @return Response Redirects to the course page
     */
    #[Route('/payment/success/course/{courseId}', name: 'payment_success_course')]
    public function paymentSuccessCourse(int $courseId, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour acheter un cursus.');
        }

        $course = $entityManager->getRepository(Course::class)->find($courseId);
        if (!$course) {
            throw $this->createNotFoundException('Cursus introuvable.');
        }

        // Check if the user already purchased the course
        $existingOrderDetail = $entityManager->getRepository(OrderDetail::class)->findOneBy([
            'course' => $course,
            'order' => $entityManager->getRepository(Order::class)->findOneBy([
                'user' => $user,
                'status' => 'paid',
            ]),
        ]);
    
        if ($existingOrderDetail) {
            $this->addFlash('info', 'Vous avez déjà acheté ce cursus.');
            return $this->redirectToRoute('theme_show', ['name' => $course->getTheme()->getName()]);
        }
        

        // Create a new order
        $order = new Order();
        $order->setUser($user);
        $order->setTotalPrice($course->getTotalPrice()); // Total des leçons du cursus
        $order->setStatus('paid');
        $order->setCreatedAt(new \DateTime());
        $order->setUpdatedAt(new \DateTime());

        $entityManager->persist($order);
        $entityManager->flush();

        // Associate all lessons of the course to the order
        foreach ($course->getLessons() as $lesson) {
            $orderDetail = new OrderDetail();
            $orderDetail->setOrder($order);
            $orderDetail->setLesson($lesson);
            $orderDetail->setPrice($lesson->getPrice());
            $orderDetail->setCourse($course); 
            $entityManager->persist($orderDetail);
        }

        // Save everything to the database
        $entityManager->flush();

        $this->addFlash('success', 'Achat du cursus réussi ! Vous avez maintenant accès à toutes les leçons.');

        return $this->redirectToRoute('theme_show', ['name' => $course->getTheme()->getName()]);
    }

    /**
     * Generates a Stripe checkout session for a course.
     *
     * @param Course $course The course to be purchased
     * @param StripeService $stripeService The Stripe service
     * @return JsonResponse Returns the Stripe session ID
     */
    #[Route('/stripe/checkout/course/{id}', name: 'stripe_checkout_course', methods: ['GET'])]
    public function createCheckoutSessionForCourse(Course $course, StripeService $stripeService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non connecté'], Response::HTTP_UNAUTHORIZED);
        }

        $checkoutSession = $stripeService->createCheckoutSessionForCourse($course, $user->getEmail());

        return new JsonResponse(['id' => $checkoutSession->id]);
    }

}