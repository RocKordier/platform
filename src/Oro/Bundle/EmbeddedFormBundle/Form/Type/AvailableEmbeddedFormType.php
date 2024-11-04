<?php

namespace Oro\Bundle\EmbeddedFormBundle\Form\Type;

use Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AvailableEmbeddedFormType extends AbstractType
{
    /**
     * @var EmbeddedFormManager
     */
    protected $manager;

    public function __construct(EmbeddedFormManager $manager)
    {
        $this->manager = $manager;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => array_flip($this->manager->getAll()),
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_available_embedded_forms';
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }
}
