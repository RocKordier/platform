define(function(require) {
    'use strict';

    var StringFilterTranslator =
        require('oroquerydesigner/js/query-type-converter/to-expression/string-filter-translator');
    var ExpressionLanguageLibrary = require('oroexpressionlanguage/js/expression-language-library');
    var BinaryNode = ExpressionLanguageLibrary.BinaryNode;
    var ConstantNode = ExpressionLanguageLibrary.ConstantNode;
    var createArrayNode = ExpressionLanguageLibrary.tools.createArrayNode;
    var createGetAttrNode = ExpressionLanguageLibrary.tools.createGetAttrNode;
    var createFunctionNode = ExpressionLanguageLibrary.tools.createFunctionNode;

    describe('oroquerydesigner/js/query-type-converter/to-expression/string-filter-translator', function() {
        var translator;
        var filterConfig = {
            type: 'string',
            name: 'string',
            choices: [
                {value: '1'},
                {value: '2'},
                {value: '3'},
                {value: '4'},
                {value: '5'},
                {value: '6'},
                {value: '7'},
                {value: 'filter_empty_option'},
                {value: 'filter_not_empty_option'}
            ]
        };

        beforeEach(function() {
            translator = new StringFilterTranslator();
        });

        describe('can not translate filter value', function() {
            var cases = {
                'when criterion type is unknown': [{
                    type: 'qux',
                    value: 'test'
                }],
                'when missing criterion type': [{
                    value: 'test'
                }]
            };

            jasmine.itEachCase(cases, function(filterValue) {
                expect(translator.test(filterValue, filterConfig)).toBe(false);
            });
        });

        describe('translate filter value', function() {
            var createLeftOperand = createGetAttrNode.bind(null, 'foo.bar');
            var cases = {
                'when filter has `contains` type': [
                    {
                        type: '1',
                        value: 'baz'
                    },
                    new BinaryNode('matches', createLeftOperand(), createFunctionNode('containsRegExp', ['baz']))
                ],
                'when filter has `not contains` type': [
                    {
                        type: '2',
                        value: 'baz'
                    },
                    new BinaryNode('not matches', createLeftOperand(), createFunctionNode('containsRegExp', ['baz']))
                ],
                'when filter has `is equal to` type': [
                    {
                        type: '3',
                        value: 'baz'
                    },
                    new BinaryNode('=', createLeftOperand(), new ConstantNode('baz'))
                ],
                'when filter has `starts with` type': [
                    {
                        type: '4',
                        value: 'baz'
                    },
                    new BinaryNode('matches', createLeftOperand(), createFunctionNode('startWithRegExp', ['baz']))
                ],
                'when filter has `ends with` type': [
                    {
                        type: '5',
                        value: 'baz'
                    },
                    new BinaryNode('matches', createLeftOperand(), createFunctionNode('endWithRegExp', ['baz']))
                ],
                'when filter has `is any of` type': [
                    {
                        type: '6',
                        value: 'baz, qux'
                    },
                    new BinaryNode('in', createLeftOperand(), createArrayNode(['baz', 'qux']))
                ],
                'when filter has `is not any of` type': [
                    {
                        type: '7',
                        value: 'baz, qux'
                    },
                    new BinaryNode('not in', createLeftOperand(), createArrayNode(['baz', 'qux']))
                ],
                'when filter has `is empty` type': [
                    {
                        type: 'filter_empty_option',
                        value: 'qux'
                    },
                    new BinaryNode('=', createLeftOperand(), new ConstantNode(''))
                ],
                'when filter has `is not empty` type': [
                    {
                        type: 'filter_not_empty_option',
                        value: 'qux'
                    },
                    new BinaryNode('!=', createLeftOperand(), new ConstantNode(''))
                ]
            };

            jasmine.itEachCase(cases, function(filterValue, expectedAST) {
                var leftOperand = createLeftOperand();

                expect(translator.test(filterValue, filterConfig)).toBe(true);
                expect(translator.translate(leftOperand, filterValue)).toEqual(expectedAST);
            });
        });
    });
});
