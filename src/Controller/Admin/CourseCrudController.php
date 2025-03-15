<?php

namespace App\Controller\Admin;

use App\Entity\Course;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;

class CourseCrudController extends AbstractCrudController
{
    /**
     * Returns the fully qualified class name of the entity managed by this CRUD controller.
     *
     * @return string The entity class name.
     */
    public static function getEntityFqcn(): string
    {
        return Course::class;
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
            TextField::new('name', 'Nom du Cursus'),
            MoneyField::new('price', 'Prix')->setCurrency('EUR')->setStoredAsCents(false),
            AssociationField::new('theme', 'Thème') 
        ];
    }
}
