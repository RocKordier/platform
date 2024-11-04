<?php

namespace Oro\Bundle\SecurityBundle\Form\Type;

use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\SecurityBundle\Acl\Domain\ObjectIdentityFactory;
use Oro\Bundle\UserBundle\Entity\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * The form type for ACL privilege label.
 */
class ObjectLabelType extends AbstractType
{
    /** @var EntityClassNameHelper */
    protected $classNameHelper;

    public function __construct(EntityClassNameHelper $classNameHelper)
    {
        $this->classNameHelper = $classNameHelper;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_acl_label';
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $identity = $view->parent->vars['value']->getId();
        $className = str_replace('entity:', '', $identity);

        // add url params for field level aces
        if (str_starts_with($identity, 'entity:') && ObjectIdentityFactory::ROOT_IDENTITY_TYPE !== $className) {
            $role = $view->parent->parent->parent->parent->vars['value'];
            $view->vars['roleId'] = $role ? $role->getId() : null;
            $view->vars['urlSafeClassName'] = $this->classNameHelper->getUrlSafeClassName($className);
            $view->vars['className'] = $className;
            $view->vars['isPlatformRole'] = ($role instanceof Role);
        }
    }

    #[\Override]
    public function getParent(): ?string
    {
        return HiddenType::class;
    }
}
