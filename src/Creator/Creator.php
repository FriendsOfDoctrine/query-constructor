<?php

namespace Informika\QueryConstructor\Creator;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Informika\QueryConstructor\MetaDataProvider\ProviderRegistry;

/**
 * @author Alexey Kharybin
 */
class Creator
{
    /**
     * @const string
     */
    const AGGREGATE_FN_COUNT = 'COUNT';
    const AGGREGATE_FN_SUM = 'SUM';
    const AGGREGATE_FN_MIN = 'MIN';
    const AGGREGATE_FN_MAX = 'MAX';
    const AGGREGATE_FN_AVG = 'AVG';

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var Joiner
     */
    protected $joiner;

    /**
     * @var ProviderRegistry
     */
    protected $registry;

    /**
     * @param EntityManagerInterface $em
     * @param ProviderRegistry $registry
     * @param Joiner $joiner
     */
    public function __construct(EntityManagerInterface $em, ProviderRegistry $registry, Joiner $joiner)
    {
        $this->em = $em;
        $this->registry = $registry;
        $this->joiner = $joiner;
    }

    /**
     * @return array
     */
    public function getSupportedEntityClasses()
    {
        return array_keys($this->registry->getRegisteredEntities());
    }

    /**
     * @return array
     */
    public function getAggregateFunctions()
    {
        return [
            static::AGGREGATE_FN_COUNT,
            static::AGGREGATE_FN_SUM,
            static::AGGREGATE_FN_MIN,
            static::AGGREGATE_FN_MAX,
            static::AGGREGATE_FN_AVG,
        ];
    }

    /**
     * @param string $value
     *
     * @return \Doctrine\ORM\QueryBuilder
     * @throws CreatorException
     */
    public function createFromJson($value, \DateTime $dateReport = null)
    {
        if (!$dateReport) {
            $dateReport = new \DateTime();
        }
        $doc = json_decode($value, true);
        $comparableOperators = ['>', '<', '=', '<=', '>='];

        if (is_null($doc)) {
            throw new CreatorException('Expected valid json document.');
        }

        if (!isset($doc['aggregateFunction'])
            || !in_array($doc['aggregateFunction'], $this->getAggregateFunctions())) {
            throw new CreatorException(sprintf(
                'Document must contain property "aggregateFunction" with one of this values: %s.',
                implode($this->getAggregateFunctions(), ',')
            ));
        }

        if (empty($doc['entity'])) {
            throw new CreatorException('Document must contain "entity" property.');
        }

        $entityClass = $doc['entity'];
        if (!in_array($entityClass, $this->getSupportedEntityClasses())) {
            throw new CreatorException(sprintf('Entity class "%s" is not supported.', $doc['entity']));
        }

        $this->joiner->setBaseEntity($entityClass);

        $entityMetaData = $this->em->getClassMetadata($entityClass);
        $entitySelectAlias = $this->joiner->getEntityAlias($entityClass);

        if (empty($doc['property'])) {
            throw new CreatorException('Document must contain "property" property.');
        }

        if (!isset($doc['conditions']) || !is_array($doc['conditions'])) {
            throw new CreatorException('JSON document must contain "conditions" property of type array.');
        }

        $aggregateFunction = $doc['aggregateFunction'];
        $property = $doc['property'];

        $qb = $this
            ->em
            ->getRepository($entityClass)
            ->createQueryBuilder($entitySelectAlias)
            ->select("{$aggregateFunction}({$entitySelectAlias}.{$property})")
        ;

        // Условия, используемые с SQL оператором WHERE
        foreach ($doc['conditions'] as $i => $rawCondition) {
            if (!empty($rawCondition['entity']) && $rawCondition['entity'] !== $entityClass) {
                $entityAlias = $this->joiner->join($qb, $rawCondition['entity'], $dateReport);
            } else {
                $entityAlias = $entitySelectAlias;
            }

            $condition = $this->parseCondition($rawCondition);
            $qbMethod = $this->getQueryBuilderMethod($condition);

            $paramName = ':val'.$i;
            $conditionValue = $condition->getValue();
            $paramType = $entityMetaData->getTypeOfField($condition->getProperty());

            if (in_array($condition->getOperator(), ['IN', 'NOT IN'])) {
                if (!is_array($conditionValue)) {
                    throw new CreatorException(
                        sprintf('Condition for the property "%s" must has "value" property of type array.',
                            $condition->getProperty())
                    );
                }

                // Если тип свойства "jsonb", используем оператор "@>",
                // Если любой другой тип, используем "IN"
                if ('jsonb' === $paramType) {
                    $qb->{$qbMethod}(sprintf(
                        'JSONB_AG(%s.%s, %s) = %s',
                        $entityAlias,
                        $condition->getProperty(),
                        $paramName,
                        'IN' === $condition->getOperator() ? 'true' : 'false'
                    ));
                    $conditionValue = json_encode($conditionValue);
                } else {
                    // Если параметр - массив для IN, нужно указать специальный тип
                    $paramType = in_array($paramType, [Type::BIGINT, Type::INTEGER, Type::SMALLINT])
                        ? Connection::PARAM_INT_ARRAY
                        : Connection::PARAM_STR_ARRAY;

                    $qb->{$qbMethod}(sprintf('%s.%s %s (%s)', $entityAlias, $condition->getProperty(), $condition->getOperator(), $paramName));
                }
            } else if (in_array($condition->getOperator(), $comparableOperators)) {
                $qb->{$qbMethod}(sprintf('%s.%s %s %s', $entityAlias, $condition->getProperty(), $condition->getOperator(), $paramName));
            } else {
                throw new CreatorException(sprintf('Operator "%s" does not exists', $condition->getOperator()));
            }
            if (in_array($paramType, [Type::DATE, Type::DATETIME, Type::DATETIMETZ])) {
                $conditionValue = new \DateTime($conditionValue);
            }

            $qb->setParameter($paramName, $conditionValue, $paramType);
        }

        $this->registry->onQueryCreated($qb, $entityClass, $entitySelectAlias, $dateReport);

        return $qb;
    }

    /**
     * @param array $condition
     *
     * @return QueryCondition
     * @throws CreatorException
     */
    protected function parseCondition(array $condition)
    {
        $expectedProps = [
            'type',
            'property',
            'operator',
            'value'
        ];

        foreach ($expectedProps as $expectedProp) {
            if (!isset($condition[$expectedProp])) {
                throw new CreatorException(sprintf('SQL condition must contain property "%s"', $expectedProp));
            }
        }

        return new QueryCondition(
            $condition['type'],
            $condition['property'],
            $condition['operator'],
            $condition['value']
        );
    }

    /**
     * @param QueryCondition $sqc
     *
     * @return string
     */
    protected function getQueryBuilderMethod(QueryCondition $sqc)
    {
        $result = 'where';

        if (QueryCondition::CONDITION_TYPE_OR === $sqc->getType()) {
            $result = 'orWhere';
        } else if (QueryCondition::CONDITION_TYPE_AND === $sqc->getType()) {
            $result = 'andWhere';
        }

        return $result;
    }
}
