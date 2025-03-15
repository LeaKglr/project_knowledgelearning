<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Course;
use App\Entity\Theme;
use App\Entity\Lesson;
use App\Entity\LessonValidation;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN')] // Only users with the ROLE_ADMIN can access the admin panel.
class AdminDashboardController extends AbstractDashboardController
{
    /**
     * Redirects to the default section in the admin panel (User management).
     *
     * @return Response Redirects to the User CRUD page.
     */
    public function index(): Response
    {
        // Automatically redirect to the User management section in the admin dashboard.
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(UserCrudController::class)->generateUrl());
    }

    /**
     * Configures the dashboard settings.
     *
     * @return Dashboard The dashboard configuration with a custom title.
     */
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Projet KnowledgeLearning'); // Custom title for the admin panel.
    }

    /**
     * Configures the menu items in the admin panel sidebar.
     *
     * @return iterable The menu structure.
     */
    public function configureMenuItems(): iterable
    {
        // Dashboard Home Link
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        // User Management Section
        yield MenuItem::section('Gestion des utilisateurs');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', User::class);

        // Course & Lesson Management Section
        yield MenuItem::section('Gestion des Formations');
        yield MenuItem::linkToCrud('Course', 'fas fa-book', Course::class);
        yield MenuItem::linkToCrud('Thèmes', 'fas fa-layer-group', Theme::class);
        yield MenuItem::linkToCrud('Leçons', 'fas fa-chalkboard-teacher', Lesson::class);
        yield MenuItem::linkToCrud('Validations des Leçons', 'fas fa-check', LessonValidation::class);
    }
}
