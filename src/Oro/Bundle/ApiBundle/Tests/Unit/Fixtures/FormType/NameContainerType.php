<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\FormType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NameContainerType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, $options['name_options']);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['name_options' => []]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'test_name_container';
    }
}
