<?php

namespace FOD\QueryConstructor\DBALTranslator;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\ORM\QueryBuilder as ORMQueryBuilder;
use Doctrine\ORM\Query\Expr\Composite;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\Query\Expr\From;
use Doctrine\ORM\Query\Expr\Select;
use Informika\DoctrineOrmToClickHouse\Mapping\ClassMetaDataFactory;

/**
 * Translates Doctrine\ORM\QueryBuilder to Doctrine\DBAL\QueryBuilder
 *
 * @author Nikita Pushkov
 */
class DBALTranslator
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ClassMetaDataFactory
     */
    protected $metadataFactory;

    /**
     * @param Connection $connection
     * @param ClassMetaDataFactory $metadataFactory // Move to Doctrine\Common\Persistence\Mapping\AbstractClassMetaDataFactory
     */
    public function __construct(Connection $connection, ClassMetaDataFactory $metadataFactory)
    {
        $this->connection = $connection;
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * @param ORMQueryBuilder $source
     * @return DBALQueryBuilder
     */
    public function translate(ORMQueryBuilder $source)
    {
        $target = $this->connection->createQueryBuilder();

        foreach ($source->getDQLParts() as $name => $dqlPart) {
            if (!$dqlPart) {
                continue;
            }

            $method = 'translate' . ucfirst($name);
            if (method_exists($this, $method)) {
                $dbalPart = (is_array($dqlPart))
                    ? array_map([$this, $method], $dqlPart)
                    : $this->{$method}($dqlPart);

                $target->add($name, $dbalPart);
            }
        }

        $dbalParams = $this->translateParameters($source->getParameters());
        $target->setParameters($dbalParams['params'], $dbalParams['paramTypes']);

        $target->setFirstResult($source->getFirstResult());
        $target->setMaxResults($source->getMaxResults());

        return $target;
    }

    /**
     * @param Select $dqlPart
     * @return string
     */
    protected function translateSelect(Select $dqlPart)
    {
        return (string) new Select(array_map([$this, 'stripAlias'], $dqlPart->getParts()));
    }

    /**
     * @param From $dqlPart
     * @return array
     */
    protected function translateFrom(From $dqlPart)
    {
        $className = $dqlPart->getFrom();
        $metaData = $this->metadataFactory->loadMetadata($className);
        $tableName = $metaData
            ? $metaData->getTable()->name
            : $className;

        return [
            'table' => $tableName,
            'alias' => $dqlPart->getAlias()
        ];
    }

    /**
     * @param Composite $dqlPart
     * @return CompositeExpression
     */
    protected function translateWhere(Composite $dqlPart)
    {
        $parts = array_map(function ($part) {
            return ($part instanceof Composite)
                ? $this->translateWhere($part) // Recursion!
                : $this->stripAlias((string) $part);
        }, $dqlPart->getParts());

        $type = ($dqlPart instanceof Orx)
            ? CompositeExpression::TYPE_OR
            : CompositeExpression::TYPE_AND;

        return new CompositeExpression($type, $parts);
    }

    /**
     * @param ArrayCollection $ormParameters
     * @return array
     */
    protected function translateParameters($ormParameters)
    {
        $params = [];
        $paramTypes = [];
        foreach ($ormParameters as $parameter) {
            $params[$parameter->getName()] = $parameter->getValue();
            $paramTypes[$parameter->getName()] = $parameter->getType();
        }

        return ['params' => $params, 'paramTypes' => $paramTypes];
    }

    /**
     * Removes alias from query part
     *
     * ClickHouse doesn't allow aliases before column names
     *
     * @param string $queryPart
     * @return string
     */
    protected function stripAlias($queryPart)
    {
        return preg_replace('/(\w+)\./', '', $queryPart);
    }
}
