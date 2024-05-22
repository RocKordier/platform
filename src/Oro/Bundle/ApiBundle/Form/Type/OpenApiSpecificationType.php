<?php

namespace Oro\Bundle\ApiBundle\Form\Type;

use Oro\Bundle\ApiBundle\Entity\OpenApiSpecification;
use Oro\Bundle\ApiBundle\Provider\OpenApiChoicesProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for OpenAPI specification creation.
 */
class OpenApiSpecificationType extends AbstractType
{
    private OpenApiChoicesProvider $openApiChoicesProvider;

    public function __construct(OpenApiChoicesProvider $openApiChoicesProvider)
    {
        $this->openApiChoicesProvider = $openApiChoicesProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label'    => 'oro.api.openapispecification.name.label',
                'required' => true
            ])
            ->add('publicSlug', TextType::class, [
                'label'    => 'oro.api.openapispecification.public_slug.label',
                'required' => false
            ])
            ->add('format', ChoiceType::class, [
                'label'    => 'oro.api.openapispecification.format.label',
                'choices'  => $this->openApiChoicesProvider->getAvailableFormatChoices(),
                'required' => true
            ])
            ->add('view', OpenApiSpecificationViewChoiceType::class, [
                'label'       => 'oro.api.openapispecification.view.label',
                'choices'     => $this->openApiChoicesProvider->getAvailableViewChoices(),
                'placeholder' => 'oro.form.choose_value',
                'required'    => true
            ])
            ->add('entities', OpenApiSpecificationEntitiesSelectType::class, [
                'label'      => 'oro.api.openapispecification.entities.label',
                'view_field' => 'view',
                'required'   => false
            ]);
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', OpenApiSpecification::class);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_openapi_specification';
    }
}
