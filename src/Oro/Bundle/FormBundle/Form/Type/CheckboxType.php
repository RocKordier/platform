<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType as ParentCheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Extends Symfony\Component\Form\Extension\Core\Type\CheckboxType to be used with $clearMissing option
 * Handles false value.
 */
class CheckboxType extends AbstractType
{
    public const NAME = 'oro_checkbox';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                if ($data === '0') {
                    $event->setData(null);
                }
            }
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ParentCheckboxType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }
}
