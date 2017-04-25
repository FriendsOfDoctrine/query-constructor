<?php

namespace FOD\QueryConstructor\Creator;

/**
 * @author Alexey Kharybin
 */
class QueryCondition
{
    const CONDITION_TYPE_AND = 'AND';
    const CONDITION_TYPE_OR = 'OR';
    const CONDITION_TYPE_NONE = 'NONE';

    const OPERATOR_TYPE_EQUALS = '=';
    const OPERATOR_TYPE_NOT_EQUALS = '!=';
    const OPERATOR_TYPE_MORE_THAN = '>';
    const OPERATOR_TYPE_MORE_THAN_OR_EQUALS = '>=';
    const OPERATOR_TYPE_LESS_THAN = '<';
    const OPERATOR_TYPE_LESS_THAN_OR_EQUALS = '<=';
    const OPERATOR_TYPE_IN = 'IN';
    const OPERATOR_TYPE_NOT_IN = 'NOT IN';
    const OPERATOR_TYPE_LIKE = 'LIKE';
    const OPERATOR_TYPE_NOT_LIKE = 'NOT LIKE';

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $property;

    /**
     * @var string
     */
    protected $operator;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @param string $type
     * @param string $property
     * @param string $operator
     * @param mixed $value
     */
    public function __construct(
        $type,
        $property,
        $operator,
        $value
    ) {
        if (!in_array($type, [
            self::CONDITION_TYPE_AND,
            self::CONDITION_TYPE_OR,
            self::CONDITION_TYPE_NONE
        ], true)) {
            throw new CreatorException(sprintf('Bad condition type given: %s', $type));
        }

        if (!in_array($operator, [
            self::OPERATOR_TYPE_EQUALS,
            self::OPERATOR_TYPE_NOT_EQUALS ,
            self::OPERATOR_TYPE_MORE_THAN,
            self::OPERATOR_TYPE_MORE_THAN_OR_EQUALS,
            self::OPERATOR_TYPE_LESS_THAN,
            self::OPERATOR_TYPE_LESS_THAN_OR_EQUALS,
            self::OPERATOR_TYPE_IN,
            self::OPERATOR_TYPE_NOT_IN,
            self::OPERATOR_TYPE_LIKE,
            self::OPERATOR_TYPE_NOT_LIKE,
        ], true)) {
            throw new CreatorException(sprintf('Bad condition operator given: %s', $operator));
        }

        $this->type = $type;
        $this->property = $property;
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}