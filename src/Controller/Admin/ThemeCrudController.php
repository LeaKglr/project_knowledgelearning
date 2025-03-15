<?php

namespace App\Controller\Admin;

use App\Entity\Theme;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ThemeCrudController extends AbstractCrudController
{
    /**
     * Returns the fully qualified class name of the entity managed by this CRUD controller.
     *
     * @return string The entity class name.
     */
    public static function getEntityFqcn(): string
    {
        return Theme::class;
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
            TextField::new('name', 'Nom du Thème'),
        ];
    }
}
