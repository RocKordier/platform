<?php

namespace Oro\Component\Testing\Unit\Form\Type\Stub;

use Symfony\Component\Form\ChoiceList\ArrayChoiceList;
use Symfony\Component\Form\ChoiceList\ChoiceListInterface;
use Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface;

/**
 * Stub for Symfony\Component\Form\ChoiceList\Loader\ChoiceLoaderInterface
 */
class ChoiceLoaderStub implements ChoiceLoaderInterface
{
    /**
     * @var ArrayChoiceList
     */
    private $choiceList;

    public function __construct(array $choices)
    {
        $this->choiceList = new ArrayChoiceList($choices, function ($givenChoice) use ($choices) {
            foreach ($choices as $value => $choice) {
                if ($choice === $givenChoice) {
                    return $value;
                }
            }
        });
    }

    #[\Override]
    public function loadChoiceList($value = null): ChoiceListInterface
    {
        return $this->choiceList;
    }

    #[\Override]
    public function loadChoicesForValues(array $values, $value = null): array
    {
        return $this->choiceList->getChoicesForValues($values);
    }

    #[\Override]
    public function loadValuesForChoices(array $choices, $value = null): array
    {
        return $this->choiceList->getValuesForChoices($choices);
    }
}
