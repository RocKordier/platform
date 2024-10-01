<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\UserBundle\Form\Provider\PasswordFieldOptionsProvider;
use Oro\Bundle\UserBundle\Form\Provider\PasswordTooltipProvider;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordComplexity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class SetPasswordType extends AbstractType
{
    /** @var PasswordTooltipProvider */
    protected $passwordTooltip;

    /** @var PasswordFieldOptionsProvider */
    protected $optionsProvider;

    public function __construct(PasswordFieldOptionsProvider $optionsProvider)
    {
        $this->optionsProvider = $optionsProvider;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'password',
            PasswordType::class,
            [
                'required' => true,
                'label' => 'oro.user.new_password.label',
                'attr' => $this->optionsProvider->getSuggestPasswordOptions(),
                'tooltip' => $this->optionsProvider->getTooltip(),
                'constraints' => [
                    new NotBlank(),
                    new PasswordComplexity($this->optionsProvider->getPasswordComplexityConstraintOptions())
                ]
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
        return 'oro_set_password';
    }

    /**
     * @return string
     */
    #[\Override]
    public function getParent(): ?string
    {
        return TextType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'compound'        => true,
            'csrf_protection' => true,
        ]);
    }
}
