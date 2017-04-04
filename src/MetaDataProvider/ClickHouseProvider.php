<?php
namespace Informika\QueryConstructor\MetaDataProvider;

use Informika\DoctrineOrmToClickHouse\Mapping\ClassMetadataFactory;
use Informika\DoctrineOrmToClickHouse\Mapping\Annotation\Column;
use Doctrine\ORM\QueryBuilder;
use Doctrine\DBAL\Types\Type;

/**
 * Class SchoolMetaDataProvider
 */
class ClickHouseProvider implements ProviderInterface
{
    protected $metadata;
    
    protected $className;
    
    /**
     * @param string $className
     */
    public function __construct(ClassMetadataFactory $classMetadataFactory, $className)
    {
        $this->className = $className;
        $this->metadata = $classMetadataFactory->loadMetadata($className);
    }
    
    /**
     * @return array
     */
    public static function getQueryConstructorMappingTypes()
    {
        return [
            Type::TARRAY => MetaDataProviderInterface::TYPE_SINGLE_CHOICE,
            Type::SIMPLE_ARRAY => MetaDataProviderInterface::TYPE_SINGLE_CHOICE,
            Type::JSON_ARRAY => MetaDataProviderInterface::TYPE_SINGLE_CHOICE,
            Type::OBJECT => MetaDataProviderInterface::TYPE_STRING,
            Type::BOOLEAN => MetaDataProviderInterface::TYPE_INTEGER,
            Type::INTEGER => MetaDataProviderInterface::TYPE_INTEGER,
            Type::SMALLINT => MetaDataProviderInterface::TYPE_INTEGER,
            Type::BIGINT => MetaDataProviderInterface::TYPE_INTEGER,
            Type::STRING => MetaDataProviderInterface::TYPE_STRING,
            Type::TEXT => MetaDataProviderInterface::TYPE_STRING,
            Type::DATETIME => MetaDataProviderInterface::TYPE_DATE,
            Type::DATETIMETZ => MetaDataProviderInterface::TYPE_DATE,
            Type::DATE => MetaDataProviderInterface::TYPE_DATE,
            Type::TIME => MetaDataProviderInterface::TYPE_DATE,
            Type::DECIMAL => MetaDataProviderInterface::TYPE_STRING,
            Type::FLOAT => MetaDataProviderInterface::TYPE_STRING,
            Type::BINARY => MetaDataProviderInterface::TYPE_STRING,
            Type::BLOB => MetaDataProviderInterface::TYPE_STRING,
            Type::GUID => MetaDataProviderInterface::TYPE_STRING,
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAggregatableProperties()
    {
        return array_reduce($this->metadata->getColumns(), function ($result, Column $column) {
            $result[$column->getPropertyName()] = $column->getPropertyName();
            return $result;
        }, []);
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return array_reduce($this->metadata->getColumns(), function ($result, Column $column) {
            $type = $column->getDBALType()->getName();
            $result[$column->getPropertyName()] = [
                'title' => $column->getPropertyName(),
                'type' => static::getQueryConstructorMappingTypes()[$type],
            ];
            return $result;
        }, []);
    }

    /**
     * {@inheritdoc}
     */
    public function getJoinableEntities()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityTitle()
    {
        return $this->getEntityClass();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return $this->className;
    }

    /**
     * {@inheritdoc}
     */
    public function onQueryCreated(QueryBuilder $qb, $entitySelectAlias, \DateTime $dateReport)
    {
    }
}
