<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\DataTransformer\ArrayToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class OroTextListType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, $this->getPreSubmitClosure());
        $builder->addModelTransformer(new ArrayToStringTransformer(",", true));
    }

    /**
     * Default value for list is empty array
     * but model transformer in reverse transform allow only strings,
     * this closure replaces empty array with empty string.
     *
     * @return callable
     */
    protected function getPreSubmitClosure()
    {
        return function (FormEvent $event) {
            $data = $event->getData();
            if (is_array($data) && empty($data)) {
                $event->setData('');
            }
        };
    }

    #[\Override]
    public function getParent(): ?string
    {
        return TextType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_textlist';
    }
}
