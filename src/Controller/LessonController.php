<?php

namespace App\Controller;

use App\Entity\Lesson;
use App\Entity\Order;
use App\Entity\OrderDetail;
use App\Entity\LessonValidation;
use App\Entity\Certification;
use App\Entity\Theme;
use App\Entity\User;
use App\Entity\Course;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LessonController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Marks a lesson as validated by the user.
     * If all lessons in the course are validated, the user earns a certification.
     *
     * @param Lesson $lesson The lesson to validate
     * @param EntityManagerInterface $entityManager The entity manager
     * @return Response Redirects back to the lesson page
     */
    #[Route('/lesson/{id}/validate', name: 'lesson_validate')]
    #[IsGranted('ROLE_USER')] // Only authenticated users can validate a lesson
    public function validateLesson(Lesson $lesson, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Check if the lesson is already validated
        $existingValidation = $entityManager->getRepository(LessonValidation::class)->findOneBy([
            'user' => $user,
            'lesson' => $lesson,
        ]);

        if ($existingValidation) {
            $this->addFlash('info', 'You have already validated this lesson.');
            return $this->redirectToRoute('lesson_show', ['id' => $lesson->getId()]);
        }

        // Create a new lesson validation record
        $lessonValidation = new LessonValidation();
        $lessonValidation->setUser($user);
        $lessonValidation->setLesson($lesson);

        $entityManager->persist($lessonValidation);
        $entityManager->flush();

        // Check if all lessons in the course are validated
        $this->checkAndValidateCourse($lesson, $entityManager);

        $this->addFlash('success', 'Lesson successfully validated!');
        return $this->redirectToRoute('lesson_show', ['id' => $lesson->getId()]);
    }

    /**
     * Checks if all lessons in a course are validated and awards certification if necessary.
     *
     * @param Lesson $lesson The lesson being validated
     * @param EntityManagerInterface $entityManager The entity manager
     */
    private function checkAndValidateCourse(Lesson $lesson, EntityManagerInterface $entityManager)
    {
        $course = $lesson->getCourse();
        $user = $this->getUser();

        if (!$course) {
            return; // If the lesson is not part of a course, do nothing
        }

        // Count validated lessons for this course
        $validatedLessons = $entityManager->getRepository(LessonValidation::class)->createQueryBuilder('lv')
            ->select('COUNT(lv.id)')
            ->join('lv.lesson', 'l')
            ->where('lv.user = :user')
            ->andWhere('lv.lesson IN (:lessons)')
            ->setParameter('user', $user)
            ->setParameter('lessons', $course->getLessons())
            ->getQuery()
            ->getSingleScalarResult();

        // If all lessons are validated, validate the course
        if ($validatedLessons == count($course->getLessons())) {
            $course->setIsValidated(true);
            $entityManager->flush();

            $this->addFlash('success', '🎉 Congratulations! You have validated the entire course: ' . $course->getName());
            $this->checkAndGrantCertification($course->getTheme(), $user, $entityManager);
        }
    }

    /**
     * Checks if a user is eligible for a certification in a theme and grants it if conditions are met.
     *
     * @param Theme $theme The theme of the course
     * @param User $user The user completing the course
     * @param EntityManagerInterface $entityManager The entity manager
     */
    private function checkAndGrantCertification(Theme $theme, User $user, EntityManagerInterface $entityManager)
    {
        $validatedCourses = $entityManager->getRepository(Course::class)->count([
            'theme' => $theme,
            'isValidated' => true
        ]);

        // Check if the user already has a certification for this theme
        $existingCertification = $entityManager->getRepository(Certification::class)->findOneBy([
            'user' => $user,
            'theme' => $theme,
        ]);

        if ($existingCertification) {
            return; // 🚫 User already has a certification, do nothing
        }

        // If all courses in the theme are validated, award certification
        if ($validatedCourses === count($theme->getCourses())) {
            $certification = new Certification($user, $theme);
            $entityManager->persist($certification);
            $entityManager->flush();
        }
    }

    /**
     * Displays a lesson page, including purchase and validation information.
     *
     * @param Lesson $lesson The lesson to display
     * @param EntityManagerInterface $entityManager The entity manager
     * @return Response Renders the lesson page
     */
    #[Route('/lesson/{id}', name: 'lesson_show')]
    public function showLesson(Lesson $lesson, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $isLessonPurchased = false;
        $validatedLessons = [];

        if ($user) {
            $order = $this->entityManager->getRepository(Order::class)->findOneBy([
                'user' => $user,
                'status' => 'paid',
            ]);

            if ($order) {
                $orderDetail = $this->entityManager->getRepository(OrderDetail::class)->findOneBy([
                    'lesson' => $lesson,
                    'order' => $order
                ]);

                if ($orderDetail) {
                    $isLessonPurchased = true;
                }
            }

            // Fetch validated lessons
            $validatedLessons = $entityManager->getRepository(LessonValidation::class)
                ->createQueryBuilder('lv')
                ->select('IDENTITY(lv.lesson)')
                ->where('lv.user = :user')
                ->setParameter('user', $user->getId())
                ->getQuery()
                ->getSingleColumnResult();
        }

        return $this->render('lesson/show.html.twig', [
            'lesson' => $lesson,
            'is_lesson_purchased' => $isLessonPurchased,
            'validatedLessons' => $validatedLessons,
        ]);
    }

    /**
     * Allows users to purchase a lesson.
     *
     * @param Lesson $lesson The lesson to purchase
     * @param EntityManagerInterface $entityManager The entity manager
     * @return Response Redirects to the lesson page
     */
    #[Route('/lesson/{id}/buy', name: 'lesson_buy')]
    #[IsGranted('ROLE_USER')] // Only authenticated users can purchase lessons
    public function buyLesson(Lesson $lesson, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        // Ensure user has verified email before purchase
        if (!$user->isVerified()) {
            $this->addFlash('error', 'You must verify your email before purchasing a lesson.');
            return $this->redirectToRoute('app_home');
        }

        // Check if the user already purchased the lesson
        $orderDetail = $this->entityManager->getRepository(OrderDetail::class)->findOneBy([
            'lesson' => $lesson,
            'order' => $this->entityManager->getRepository(Order::class)->findOneBy([
                'user' => $user,
                'status' => 'paid',
            ])
        ]);

        if ($orderDetail) {
            $this->addFlash('info', 'You have already purchased this lesson.');
            return $this->redirectToRoute('lesson_show', ['id' => $lesson->getId()]);
        }

        // Simulate purchase (a real payment system should be implemented)
        $user->addPurchasedLesson($lesson);
        $entityManager->persist($user);
        $entityManager->flush();

        $this->addFlash('success', 'Lesson successfully purchased!');
        return $this->redirectToRoute('lesson_show', ['id' => $lesson->getId()]);
    }
}
