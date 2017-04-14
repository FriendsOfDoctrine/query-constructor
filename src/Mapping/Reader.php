<?php

namespace FOD\QueryConstructor\Mapping;

use Doctrine\Common\Annotations\Reader as AnnotationReader;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata as OrmClassMetadata;
use FOD\QueryConstructor\Mapping\Annotation\Entity;
use FOD\QueryConstructor\Mapping\Annotation\Property;

/**
 * @author Nikita Pushkov
 */
class Reader
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var AnnotationReader
     */
    protected $reader;

    /**
     * @var array
     */
    protected $associations;

    /**
     * Constructor
     *
     * @param EntityManager $em
     * @param AnnotationReader $reader
     */
    public function __construct(EntityManager $em, AnnotationReader $reader)
    {
        $this->em = $em;
        $this->reader = $reader;
    }

    /**
     * @param OrmClassMetadata $metaData
     * @return ClassMetadata|null
     */
    public function getClassMetaData(OrmClassMetadata $metaData)
    {
        $reflection = $metaData->getReflectionClass();
        $entityMetadata = $this->reader->getClassAnnotation(
            $reflection,
            Entity::CLASSNAME
        );
        if (!$entityMetadata) {
            return null;
        }
        $classMetadata = new ClassMetadata($reflection->getName(), $entityMetadata);
        $properties = $metaData->getReflectionProperties();

        $aggregatableProperties = $this->filterOnlyExcept($properties, $entityMetadata->getAggregatableFields(), $entityMetadata->getAggregatableFieldsExcept());
        $classMetadata->setAggregatableProperties($this->fetchProperties($metaData, $aggregatableProperties));

        $filterableProperties = $this->filterOnlyExcept($properties, $entityMetadata->getFilterableFields(), $entityMetadata->getFilterableFieldsExcept());
        $classMetadata->setProperties($this->fetchProperties($metaData, $filterableProperties));

        $classMetadata->setJoins($this->makeJoins($metaData));

        return $classMetadata;
    }

    /**
     * @param array $properties
     * @param array $only
     * @param array $except
     * @return array
     */
    protected function filterOnlyExcept(array $properties, array $only, array $except)
    {
        return $this->filterExcept($this->filterOnly($properties, $only), $except);
    }

    /**
     * @param array $properties
     * @param array $names
     * @return array
     */
    protected function filterOnly(array $properties, array $names = null)
    {
        if ($names) {
            return array_filter($properties, function (\ReflectionProperty $property) use ($names) {
                return in_array($property->getName(), $names);
            });
        } else {
            return $properties;
        }
    }

    /**
     * @param array $properties
     * @param array $names
     * @return array
     */
    protected function filterExcept(array $properties, array $names = null)
    {
        if ($names) {
            return array_filter($properties, function (\ReflectionProperty $property) use ($names) {
                return !in_array($property->getName(), $names);
            });
        } else {
            return $properties;
        }
    }

    /**
     * @param OrmClassMetadata $metadata
     * @param array $properties
     * @return array
     */
    protected function fetchProperties(OrmClassMetadata $metadata, array $properties)
    {
        $result = [];
        foreach ($properties as $property) {
            if (!isset($this->getAssociations($metadata)[$property->getName()]['qcMetadata'])) {
                $result[$property->getName()] = $this->makePropertyFromReflection($metadata, $property);
            }
        }

        return $result;
    }

    /**
     * @param OrmClassMetadata $metadata
     * @param \ReflectionProperty $property
     * @return Property
     */
    protected function makePropertyFromReflection(OrmClassMetadata $metadata, \ReflectionProperty $property)
    {
        $propertyMetadata = $this->reader->getPropertyAnnotation($property, Property::CLASSNAME);
        if (!$propertyMetadata) {
            $propertyMetadata = new Property();
        }

        if (!$propertyMetadata->title) {
            $propertyMetadata->title = ucfirst($property->getName());
        }

        if (!$propertyMetadata->choices && isset($this->getAssociations($metadata)[$property->getName()]['targetEntity'])) {
            $titleField = isset($propertyMetadata->titleField) ? $propertyMetadata->titleField : null;
            list($valueField, $titleField) = $this->detectListFields(
                $metadata,
                $property->getName(),
                $this->getAssociations($metadata)[$property->getName()]['targetEntity'],
                $titleField
            );
            $propertyMetadata->choices = $this->loadChoices(
                $this->getAssociations($metadata)[$property->getName()]['targetEntity'],
                $valueField,
                $titleField
            );
        }

        if (!$propertyMetadata->type) {
            if (is_array($propertyMetadata->choices) && count($propertyMetadata->choices) > 0) {
                $propertyMetadata->type = Property::TYPE_MULTIPLE_CHOICE;
            } else {
                $propertyMetadata->type = $this->mapPropertyTypeFromDoctrine($metadata->getTypeOfField($property->getName()));
            }
        }

        return $propertyMetadata;
    }

    /**
     * @param OrmClassMetadata $metadata
     * @param string $propertyName
     * @param string $targetClassName
     * @param string $titleField
     * @return array
     * @throws \LogicException
     */
    protected function detectListFields(OrmClassMetadata $metadata, $propertyName, $targetClassName, $titleField = null)
    {
        $valueColumn = $metadata->getSingleAssociationReferencedJoinColumnName($propertyName);
        $referencedMetadata = $this->em->getMetadataFactory()->getMetadataFor($targetClassName);
        $valueField = $referencedMetadata->getFieldName($valueColumn);
        if (!$titleField) {
            foreach ($referencedMetadata->getFieldNames() as $fieldName) {
                if ($fieldName === $valueField) {
                    continue;
                }
                if (in_array($referencedMetadata->getTypeOfField($fieldName), [Type::STRING, Type::TEXT])) {
                    $titleField = $fieldName;
                    break;
                }
            }
            if (!$titleField) {
                throw new \LogicException(sprintf('String title field not found in class [%s]. Specify titleField option manually.', $targetClassName));
            }
        }
        return [$valueField, $titleField];
    }

    /**
     * @param string $className
     * @param string $valueField
     * @param string $titleField
     * @return array
     */
    protected function loadChoices($className, $valueField, $titleField)
    {
        $resultSet = $this->em->createQueryBuilder()
            ->select("PARTIAL e.{{$valueField}, {$titleField}}")
            ->from($className, 'e')
            ->getQuery()
            ->getArrayResult();

        // PHP5.4 support: instead of array_column($resultSet, $titleField, $valueField)
        $resultMapped = array_reduce($resultSet, function (array $result, array $item) use ($valueField, $titleField) {
            $result[$item[$valueField]] = $item[$titleField];
            return $result;
        }, []);

        asort($resultMapped);

        return $resultMapped;
    }

    /**
     * @param string $propertyType
     * @return string
     */
    protected function mapPropertyTypeFromDoctrine($propertyType)
    {
        switch ($propertyType) {
            case Type::BOOLEAN:
            case Type::BIGINT:
            case Type::INTEGER:
            case Type::SMALLINT:
                return Property::TYPE_INTEGER;
            case Type::DATETIME:
            case Type::DATETIMETZ:
            case Type::DATE:
            case Type::TIME:
                return Property::TYPE_DATE;
            default:
                return Property::TYPE_STRING;
        }
    }

    /**
     * @param OrmClassMetadata $metadata
     * @return array
     */
    protected function getAssociations(OrmClassMetadata $metadata)
    {
        if (is_null($this->associations)) {
            $this->associations = [];
            foreach ($metadata->getAssociationMappings() as $field => $mapping) {
                if (!$mapping['isOwningSide']) {
                    continue;
                }
                $targetClassName = $mapping['targetEntity'];
                $ormClassMetadata = $this->em->getMetadataFactory()->getMetadataFor($targetClassName);

                $entityMetadata = $this->reader->getClassAnnotation(
                    $ormClassMetadata->getReflectionClass(),
                    Entity::CLASSNAME
                );

                $this->associations[$field] = [
                    'qcMetadata' => $entityMetadata,
                    'targetEntity' => $targetClassName,
                ];
            }
        }
        return $this->associations;
    }

    /**
     * @param OrmClassMetadata $metadata
     * @return array
     */
    protected function makeJoins(OrmClassMetadata $metadata)
    {
        $result = [];

        $joinableAssociations = array_filter($this->getAssociations($metadata), function ($association) {
            return $association['qcMetadata'];
        });

        foreach ($joinableAssociations as $propertyName => $association) {
            $result[$association['targetEntity']] = $propertyName;
        }

        return $result;
    }
}
