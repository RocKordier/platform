<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;

/**
 * Represents GREATER THAN OR EQUAL TO comparison expression.
 */
class GteComparisonExpression implements ComparisonExpressionInterface
{
    #[\Override]
    public function walkComparisonExpression(
        QueryExpressionVisitor $visitor,
        string $field,
        string $expression,
        string $parameterName,
        mixed $value
    ): mixed {
        $visitor->addParameter($parameterName, $value);

        return $visitor->getExpressionBuilder()
            ->gte($expression, $visitor->buildPlaceholder($parameterName));
    }
}
