<?php

namespace FOD\QueryConstructor\Mapping;

use Doctrine\Common\Annotations\Reader as AnnotationReader;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata as OrmClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
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
     * @var AssociationMetaData[][]
     */
    protected $associations;

    /** @var array */
    protected $invertAssociations = [];

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
     *
     * @return ClassMetadata|null
     */
    public function getClassMetaData(OrmClassMetadata $metaData)
    {
        /** @var Entity $entityMetadata */
        if ($entityMetadata = $this->reader->getClassAnnotation($metaData->getReflectionClass(), Entity::CLASSNAME)) {

            $classMetadata = new ClassMetadata($metaData->getReflectionClass()->getName(), $entityMetadata);

            $classMetadata->setAggregatableProperties($this->fetchProperties($metaData,
                $this->filterOnlyExcept(
                    $metaData->getReflectionProperties(),
                    array_reduce($metaData->fieldMappings, function ($properties, $property) {
                        if (in_array($property['type'], [Type::INTEGER, Type::SMALLINT, Type::BIGINT, Type::FLOAT, Type::DECIMAL, Type::BINARY], true)) {
                            $properties[] = $property['fieldName'];
                        }
                        return $properties;
                    }),
                    $entityMetadata->getAggregatableFieldsExcept()
                )
            ));

            $classMetadata->setProperties($this->fetchProperties($metaData));

            $classMetadata->setJoins($this->makeJoins($metaData));

            return $classMetadata;
        }

        return null;
    }

    /**
     * @param array $properties
     * @param array $only
     * @param array $except
     *
     * @return array
     */
    protected function filterOnlyExcept(array $properties, array $only, array $except)
    {
        return $this->filterExcept($this->filterOnly($properties, $only), $except);
    }

    /**
     * @param array $properties
     * @param array $names
     *
     * @return array
     */
    protected function filterOnly(array $properties, array $names = null)
    {
        return $names ? array_filter($properties, function (\ReflectionProperty $property) use ($names) {
            return in_array($property->getName(), $names, true);
        }) : $properties;
    }

    /**
     * @param array $properties
     * @param array $names
     *
     * @return array
     */
    protected function filterExcept(array $properties, array $names = null)
    {
        return $names ? array_filter($properties, function (\ReflectionProperty $property) use ($names) {
            return !in_array($property->getName(), $names, true);
        }) : $properties;
    }

    /**
     * @param OrmClassMetadata $metadata
     * @param array $properties
     *
     * @return Property[]
     */
    protected function fetchProperties(OrmClassMetadata $metadata, array $properties = [])
    {
        $result = [];
        $oneToMany = [];
        if (!$properties) {
            $properties = $metadata->getReflectionProperties();
        }

        /** @var \ReflectionProperty $property */
        foreach ($properties as $property) {
            if (!isset($this->getAssociations($metadata)[$property->getName()])) {
                $propertyType = null;
                if (isset($metadata->associationMappings[$property->getName()]) && !isset($metadata->associationMappings[$property->getName()]['joinColumns'])) {
                    $result['COUNT(' . $property->getName() . ')'] = $this->makePropertyFromReflection($metadata, $property, Property::TYPE_AGGREGATE, 'COUNT(' . $property->getName() . ')');
                    $propertyType = Property::TYPE_MULTIPLE_CHOICE;
                }
                $result[$property->getName()] = $this->makePropertyFromReflection($metadata, $property, $propertyType);
            }
        }

        return $result;
    }

    /**
     * @param OrmClassMetadata $metadata
     * @param \ReflectionProperty $property
     * @param string $propertyType
     * @param string $propertyTitle
     *
     * @return Property
     */
    protected function makePropertyFromReflection(OrmClassMetadata $metadata, \ReflectionProperty $property, $propertyType = null, $propertyTitle = null)
    {
        $propertyMetadata = $this->reader->getPropertyAnnotation($property, Property::CLASSNAME);
        if (!$propertyMetadata) {
            $propertyMetadata = new Property();
        }

        if (!$propertyMetadata->title) {
            $propertyMetadata->title = $propertyTitle ?: ucfirst($property->getName());
        }

        if (!$propertyMetadata->choices && isset($metadata->associationMappings[$property->getName()])) {
            $titleField = isset($propertyMetadata->titleField) ? $propertyMetadata->titleField : null;
            list($valueField, $titleField) = $this->detectListFields(
                $metadata,
                $property->getName(),
                $metadata->associationMappings[$property->getName()]['targetEntity'],
                $titleField
            );

            $propertyMetadata->choices = $this->loadChoices(
                $metadata->associationMappings[$property->getName()]['targetEntity'],
                $valueField,
                $titleField
            );
        }

        if (!$propertyMetadata->type) {

            if (null !== $propertyType) {
                if (!in_array($propertyType, Property::getTypes(), true)) {
                    throw new \InvalidArgumentException('Property type \'' . $propertyType . '\' is not valid');
                }
                $propertyMetadata->type = $propertyType;
            } elseif (is_array($propertyMetadata->choices) && count($propertyMetadata->choices) > 0) {
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
     *
     * @return array
     * @throws \LogicException
     */
    protected function detectListFields(OrmClassMetadata $metadata, $propertyName, $targetClassName, $titleField = null)
    {
        $referencedMetadata = $this->em->getMetadataFactory()->getMetadataFor($targetClassName);

        try {
            $valueField = $referencedMetadata->getFieldName($metadata->getSingleAssociationReferencedJoinColumnName($propertyName));
        } catch (MappingException $exception) {
            $valueField = $referencedMetadata->getIdentifierFieldNames();
        }

        if (!$titleField) {
            foreach ($referencedMetadata->getFieldNames() as $fieldName) {
                if ($fieldName === $valueField) {
                    continue;
                }
                if (in_array($referencedMetadata->getTypeOfField($fieldName), [Type::STRING, Type::TEXT], true)) {
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
     *
     * @return array
     */
    protected function loadChoices($className, $valueField, $titleField)
    {
        $resultSet = $this->em->createQueryBuilder()
            ->select("PARTIAL e.{" . implode(', ', array_merge([$titleField], (array)$valueField)) . "}")
            ->from($className, 'e')
            ->getQuery()
            ->getArrayResult();

        // PHP5.4 support: instead of array_column($resultSet, $titleField, $valueField)
        $resultMapped = array_reduce($resultSet, function (array $result, array $item) use ($valueField, $titleField) {
            $valueField = (array)$valueField;
            if (count($valueField) > 1) {
                $key = [];
                foreach ($valueField as $vField) {
                    $key[] = $vField . ':' . $item[$vField];
                }
                $result[implode(';', $key)] = $item[$titleField];
            } else {
                $result[$item[current($valueField)]] = $item[current($valueField)] . ': ' . $item[$titleField];
            }

            return $result;
        }, []);

        asort($resultMapped);

        return $resultMapped;
    }

    /**
     * @param string $propertyType
     *
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
     *
     * @return array
     */
    protected function getAssociations(OrmClassMetadata $metadata)
    {
        if (isset($this->associations[$metadata->name])) {
            return $this->associations[$metadata->name];
        }

        $this->associations[$metadata->name] = [];

        foreach ($metadata->getAssociationMappings() as $field => $mapping) {
            if (!$mapping['isOwningSide']) {
                $targetClassName = $mapping['targetEntity'];
                $ormClassMetadata = $this->em->getMetadataFactory()->getMetadataFor($targetClassName);

                $entityMetadata = $this->reader->getClassAnnotation(
                    $ormClassMetadata->getReflectionClass(),
                    Entity::CLASSNAME
                );

                $this->invertAssociations[$metadata->name][$field] = new AssociationMetaData($targetClassName, $entityMetadata, $this->getAssociations($ormClassMetadata));
            } else {
                $targetClassName = $mapping['targetEntity'];
                $ormClassMetadata = $this->em->getMetadataFactory()->getMetadataFor($targetClassName);

                $entityMetadata = $this->reader->getClassAnnotation(
                    $ormClassMetadata->getReflectionClass(),
                    Entity::CLASSNAME
                );

                $this->associations[$metadata->name][$field] = new AssociationMetaData($targetClassName, $entityMetadata, $this->getAssociations($ormClassMetadata));
            }
        }

        return $this->associations[$metadata->name];
    }

    /**
     * @param OrmClassMetadata $metadata
     *
     * @return array
     */
    protected function makeJoins(OrmClassMetadata $metadata)
    {
        $result = [];

        $joinableAssociations = array_filter($this->getAssociations($metadata), function ($association) {
            /** @var AssociationMetaData $association */
            return $association->getEntityMetadata();
        });

        /**
         * @var string $propertyName
         * @var AssociationMetaData $association
         */
        foreach ($joinableAssociations as $propertyName => $association) {
            $result[$association->getTargetEntity()] = $propertyName;

            $result = array_merge($result, $this->getRelatedJoins($association->getTargetEntity()));
        }

        return $result;
    }

    /**
     * @param $className
     *
     * @return array
     */
    protected function getRelatedJoins($className)
    {
        $relatedEntities = [];
        if (isset($this->associations[$className]) && !empty($this->associations[$className])) {
            /**
             * @var string $propertyName
             * @var AssociationMetaData $association
             */
            foreach ($this->associations[$className] as $propertyName => $association) {
                $relatedEntities[$association->getTargetEntity()] = $propertyName;
                $relatedEntities = array_merge($relatedEntities, $this->getRelatedJoins($association->getTargetEntity()));
            }
        }

        return $relatedEntities;
    }
}
