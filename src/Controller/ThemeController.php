<?php

namespace App\Controller;

use App\Entity\Theme;
use App\Entity\Course;
use App\Entity\User;
use App\Entity\Order;
use App\Entity\OrderDetail;
use App\Entity\Lesson;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ThemeController extends AbstractController
{
    /**
     * Displays the list of available themes.
     *
     * @param EntityManagerInterface $entityManager Doctrine entity manager
     * @return Response
     */
    #[Route('/theme', name: 'app_theme')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // Retrieve all themes from the database
        $themes = $entityManager->getRepository(Theme::class)->findAll(); // 🔥 Récupérer tous les thèmes

        return $this->render('theme/index.html.twig', [
            'themes' => $themes,
        ]);
    }

    /**
     * Displays details of a theme and its related courses.
     *
     * @param string $name Theme name (slug)
     * @param EntityManagerInterface $entityManager Doctrine entity manager
     * @return Response
     */
    #[Route('/theme/{name}', name: 'theme_show')]
    public function showTheme(string $name, EntityManagerInterface $entityManager): Response
    {
        // Retrieve the theme by its name
        $theme = $entityManager->getRepository(Theme::class)->findOneBy(['name' => $name]);

        if (!$theme) {
            throw $this->createNotFoundException("Thème introuvable : $name");
        }

        // Retrieve courses associated with the theme
        $courses = $entityManager->getRepository(Course::class)->findBy(['theme' => $theme]);
        
        // Initialization of purchase-related variables
        $purchasedCourses = [];      
        $partiallyPurchasedCourses = [];
        $purchasedLessons = [];
        $lessons = [];

        /** @var ?User $user */
        $user = $this->getUser();

        if ($user) {
            // Retrieve lessons purchased by the user
            $purchasedLessons = $entityManager->getRepository(OrderDetail::class)
            ->createQueryBuilder('od')
            ->select('IDENTITY(od.lesson)') // Retrieve only the IDs of purchased lessons
            ->join('od.order', 'o')         
            ->join('od.course', 'c')
            ->where('o.user = :user')
            ->andWhere('o.status = :status') // Only paid orders
            ->setParameter('user', $user->getId())
            ->setParameter('status', 'paid')
            ->getQuery()
            ->getSingleColumnResult();

        }     

        // Retrieve all lessons from each course
        foreach ($courses as $course) {
            foreach ($course->getLessons() as $lesson) {
                $lessons[] = $lesson;
            }
        }

        // Check which courses are fully purchased or partially purchased
        foreach ($courses as $course) {
            $lessonCount = count($course->getLessons()); // Total number of lessons in the course
        
            $purchasedLessonCount = $entityManager->getRepository(OrderDetail::class)->createQueryBuilder('od')
                ->select('COUNT(od.id)')
                ->join('od.order', 'o')
                ->where('o.user = :user')
                ->andWhere('o.status = :status')
                ->andWhere('od.course = :course')
                ->setParameter('user', $user->getId())
                ->setParameter('status', 'paid')
                ->setParameter('course', $course)
                ->getQuery()
                ->getSingleScalarResult(); // Get the number of purchased lessons for this course
        
            if ($purchasedLessonCount == $lessonCount) {
                // Mark the course as fully purchased
                $purchasedCourses[] = $course->getId(); 
            } elseif ($purchasedLessonCount > 0) {
                // Course partially purchased (prevents full purchase)
                $partiallyPurchasedCourses[] = $course->getId();
            }
        }

        // Check validated courses
        $validatedCourses = [];
        if ($user) {
            $validatedCourses = $entityManager->getRepository(Course::class)->createQueryBuilder('c')
                ->select('c.id')
                ->join('c.lessons', 'l')
                ->join('App\Entity\LessonValidation', 'lv', 'WITH', 'lv.lesson = l.id AND lv.user = :user')
                ->groupBy('c.id')
                ->having('COUNT(l.id) = (SELECT COUNT(l2.id) FROM App\Entity\Lesson l2 WHERE l2.course = c)')
                ->setParameter('user', $user->getId())
                ->getQuery()
                ->getSingleColumnResult();
        }

        return $this->render('theme/show.html.twig', [
            'theme' => $theme,
            'courses' => $courses,
            'purchasedLessons' => $purchasedLessons,
            'lessons' => $lessons,
            'purchasedCourses' => $purchasedCourses, 
            'partiallyPurchasedCourses' => $partiallyPurchasedCourses,
            'validatedCourses' => $validatedCourses, 
        ]);
    }

    /**
     * Checks if a user has validated a lesson.
     *
     * @param User $user The user
     * @param Lesson $lesson The lesson to check
     * @param EntityManagerInterface $entityManager Doctrine entity manager
     * @return bool True if the lesson has been validated by the user
     */
    private function userHasLesson(User $user, Lesson $lesson, EntityManagerInterface $entityManager): bool
    {
        $query = $entityManager->createQuery(
            'SELECT COUNT(lv.id) 
             FROM App\Entity\LessonValidation lv 
             WHERE lv.user = :user AND lv.lesson = :lesson'
        )->setParameters([
            'user' => $user,
            'lesson' => $lesson,
        ]);

        return (bool) $query->getSingleScalarResult();
    }
}
