<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * General choice form type.
 * Makes use of Select2 widget.
 */
class OroChoiceType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $defaults = [
            'placeholder' => 'oro.form.choose_value',
            'allowClear' => true,
        ];

        $resolver->setDefaults(
            [
                'configs' => $defaults,
            ]
        );

        // OptionsResolver doesn't support merging of second level.
        // Without normalizer "configs" will be overridden (not merged) by user array
        $resolver->setNormalizer('configs', static fn (Options $options, $configs) => array_merge($defaults, $configs));
    }

    #[\Override]
    public function getParent(): ?string
    {
        return Select2ChoiceType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_choice';
    }
}
