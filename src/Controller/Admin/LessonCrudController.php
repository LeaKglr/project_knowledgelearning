<?php

namespace App\Controller\Admin;

use App\Entity\Lesson;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;

class LessonCrudController extends AbstractCrudController
{
    /**
     * Returns the fully qualified class name of the entity managed by this CRUD controller.
     *
     * @return string The entity class name.
     */
    public static function getEntityFqcn(): string
    {
        return Lesson::class;
    }

    /**
     * Configures the fields displayed in the admin panel.
     *
     * @param string $pageName The name of the current page (e.g., index, detail, edit, new).
     * @return iterable The list of fields to be displayed.
     */
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('title', 'Titre de la Leçon'),
            TextareaField::new('description', 'Fiche Descriptive')->hideOnIndex(),
            AssociationField::new('course', 'Course'),
            TextField::new('video', 'URL de la Vidéo')->setHelp('Ex: https://www.youtube.com/watch?v=XYZ123'),
            MoneyField::new('price', 'Prix')->setCurrency('EUR')->setStoredAsCents(false),
            DateTimeField::new('createdAt', 'Créé le')->hideOnForm(),
            DateTimeField::new('updatedAt', 'Mis à jour le')->hideOnForm(),
        ];
    }
}
