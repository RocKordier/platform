<?php

namespace Oro\Bundle\ReportBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityChoiceType;

class ReportEntityChoiceType extends EntityChoiceType
{
    #[\Override]
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_report_entity_choice';
    }
}
