import __ from 'orotranslation/js/translator';
import ASTNodeWrapper from 'oroexpressionlanguage/js/ast-node-wrapper';
import {ConditionalNode, GetAttrNode, NameNode} from 'oroexpressionlanguage/js/expression-language-library';

/**
 * @typedef {Object} EntityInfo
 * @property {boolean} isCollection - says if entity has to be used like collection
 * @property {string} name - alias or className of entity
 * @property {EntityTreeNode} fields - node of entityTree that contains its fields
 */

/**
 * @typedef {Object} OperationInfo
 * @property {string} item - an operator (e.g. '+', '*')
 * @property {string} type - type of operator (e.g. 'math', 'equal')
 */

class ExpressionOperandTypeValidator {
    /**
     * @param {Object} options
     * @param {Array.<EntityInfo>?} options.entities - array with objects that contain information about root entities
     * @param {Object.<string, OperationInfo>?} options.operations - keys are strings of operators (e.g. '+', '*')
     * @param {number=} options.itemLevelLimit - says how deep in entityTree can be used a field
     * @param {boolean=} options.isConditionalNodeAllowed - when false an exeption is thrown if conditional node is used
     */
    constructor({entities = null, operations = null, itemLevelLimit = 3, isConditionalNodeAllowed = true}) {
        Object.assign(this, {entities, itemLevelLimit, operations, isConditionalNodeAllowed});
    }

    /**
     * Tests a parsed expression.
     *
     * @param {ParsedExpression} parsedExpression - instance of ParsedExpression that created by ExpressionLanguage
     * @throws {TypeError} if the expression contains wrong entity fields or operations
     */
    expectValid(parsedExpression) {
        const astNodeWrapper = new ASTNodeWrapper(parsedExpression.getNodes());
        if (!this.isConditionalNodeAllowed) {
            this._expectWithoutConditionals(astNodeWrapper);
        }
        if (this.operations) {
            this._expectAllowedOperators(astNodeWrapper);
        }
        if (this.entities) {
            this._expectValidPropertyPath(astNodeWrapper);
        }
    }

    /**
     * @param {ASTNodeWrapper} astNodeWrapper - instance of ASTNodeWrapper that wraps nodes created by
     *                                          ExpressionLanguage
     * @throws {TypeError} if node tree contains conditionals nodes
     * @protected
     */
    _expectWithoutConditionals(astNodeWrapper) {
        if (astNodeWrapper.findInstancesOf(ConditionalNode).length !== 0) {
            throw new TypeError(__('oro.form.expression_editor.validation.conditional'));
        }
    }

    /**
     * @param {ASTNodeWrapper} astNodeWrapper - instance of ASTNodeWrapper that wraps nodes created by
     *                                          ExpressionLanguage
     * @throws {TypeError} if node tree contains forbidden operators
     * @protected
     */
    _expectAllowedOperators(astNodeWrapper) {
        const forbiddenOperatorNodes = astNodeWrapper.findAll(node => {
            const operator = node.attr('operator');
            return operator !== void 0 && !(operator in this.operations);
        });
        if (forbiddenOperatorNodes.length !== 0) {
            let message;
            const operators = forbiddenOperatorNodes
                .map(node => node.attr('operator'))
                .filter((val, i, arr) => arr.indexOf(val) === i);
            if (operators.length === 1) {
                message = __('oro.form.expression_editor.validation.forbidden_operator', {
                    operator: forbiddenOperatorNodes[0].attr('operator')
                });
            } else {
                message = __('oro.form.expression_editor.validation.forbidden_operator', {
                    operators: operators.join('`, `')
                });
            }
            throw new TypeError(message);
        }
    }

    /**
     * @param {ASTNodeWrapper} astNodeWrapper - instance of ASTNodeWrapper that wraps nodes created by
     *                                          ExpressionLanguage
     * @throws {TypeError} if node tree contains invalid property paths
     * @protected
     */
    _expectValidPropertyPath(astNodeWrapper) {
        this.entities.forEach(entity => {
            const nameNodes = astNodeWrapper
                .findAll(node => node.instanceOf(NameNode) && node.attr('name') === entity.name);

            nameNodes.forEach(node => {
                if (node.parent === null || !node.parent.instanceOf(GetAttrNode)) {
                    throw new TypeError(__('oro.form.expression_editor.validation.property_path.like_variable', {
                        name: entity.name
                    }));
                }
                let source = entity.name;
                if (entity.isCollection) {
                    if (node.parent.attr('type') !== GetAttrNode.ARRAY_CALL) {
                        throw new TypeError(
                            __('oro.form.expression_editor.validation.property_path.like_single_entity', {
                                name: entity.name
                            })
                        );
                    }
                    source += '[' + node.parent.child(1).attr('value') + ']';
                    node = node.parent;
                } else if (node.parent.attr('type') !== GetAttrNode.PROPERTY_CALL) {
                    throw new TypeError(__('oro.form.expression_editor.validation.property_path.like_collection', {
                        name: entity.name
                    }));
                }
                node = node.parent;
                let fieldName;
                let level = 1;
                let field = entity.fields;
                let isEnumerableField = false;
                while (node && node.instanceOf(GetAttrNode)) {
                    level++;
                    fieldName = node.child(1).attr('value');
                    field = field[fieldName];
                    if (field && field.__field && field.__field['type'] === 'enum') {
                        isEnumerableField = true;
                    }
                    if (!field && !isEnumerableField) {
                        throw new TypeError(
                            __('oro.form.expression_editor.validation.property_path.field_not_present', {
                                fieldName,
                                source
                            })
                        );
                    } else if (level > this.itemLevelLimit) {
                        throw new TypeError(__('oro.form.expression_editor.validation.property_path.level_limit', {
                            fieldName,
                            source
                        }));
                    }
                    node = node.parent;
                    source += '.' + fieldName;
                }
                if (field && !field.__isField) {
                    throw new TypeError(__('oro.form.expression_editor.validation.property_path.not_field', {
                        source
                    }));
                }
            });
        });
    }
}

export default ExpressionOperandTypeValidator;
