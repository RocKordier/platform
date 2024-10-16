<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Condition;

use Oro\Component\ConfigExpression\Condition;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

class LessThanTest extends \PHPUnit\Framework\TestCase
{
    /** @var Condition\LessThan */
    protected $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->condition = new Condition\LessThan();
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, $context, $expectedResult)
    {
        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    public function evaluateDataProvider(): array
    {
        $options = ['left' => new PropertyPath('foo'), 'right' => new PropertyPath('bar')];

        return [
            'less_than'    => [
                'options'        => $options,
                'context'        => ['foo' => 50, 'bar' => 100],
                'expectedResult' => true
            ],
            'greater_than' => [
                'options'        => $options,
                'context'        => ['foo' => 100, 'bar' => 50],
                'expectedResult' => false
            ],
            'equal'        => [
                'options'        => $options,
                'context'        => ['foo' => 50, 'bar' => 50],
                'expectedResult' => false
            ]
        ];
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray($options, $message, $expected)
    {
        $this->condition->initialize($options);
        if ($message !== null) {
            $this->condition->setMessage($message);
        }
        $actual = $this->condition->toArray();
        $this->assertEquals($expected, $actual);
    }

    public function toArrayDataProvider(): array
    {
        return [
            [
                'options'  => ['left', 'right'],
                'message'  => null,
                'expected' => [
                    '@lt' => [
                        'parameters' => [
                            'left',
                            'right'
                        ]
                    ]
                ]
            ],
            [
                'options'  => ['left', 'right'],
                'message'  => 'Test',
                'expected' => [
                    '@lt' => [
                        'message'    => 'Test',
                        'parameters' => [
                            'left',
                            'right'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider compileDataProvider
     */
    public function testCompile($options, $message, $expected)
    {
        $this->condition->initialize($options);
        if ($message !== null) {
            $this->condition->setMessage($message);
        }
        $actual = $this->condition->compile('$factory');
        $this->assertEquals($expected, $actual);
    }

    public function compileDataProvider(): array
    {
        return [
            [
                'options'  => [new PropertyPath('foo'), 123],
                'message'  => null,
                'expected' => '$factory->create(\'lt\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'foo\', [\'foo\'], [false])'
                    . ', 123])'
            ],
            [
                'options'  => [new PropertyPath('foo'), 123],
                'message'  => 'Test',
                'expected' => '$factory->create(\'lt\', ['
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'foo\', [\'foo\'], [false])'
                    . ', 123])->setMessage(\'Test\')'
            ]
        ];
    }
}
