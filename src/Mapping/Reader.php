<?php

namespace Informika\QueryConstructor\Mapping;

use Doctrine\Common\Annotations\Reader as AnnotationReader;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata as OrmClassMetadata;
use Informika\QueryConstructor\Mapping\Annotation\Entity;
use Informika\QueryConstructor\Mapping\Annotation\Property;

/**
 * @author Nikita Pushkov
 */
class Reader
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var AnnotationReader
     */
    protected $reader;

    /**
     * Constructor
     *
     * @param EntityManagerInterface $em
     * @param AnnotationReader $reader
     */
    public function __construct(EntityManagerInterface $em, AnnotationReader $reader)
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

        $classMetadata->setJoins($this->makeJoins($properties));

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
            $result[$property->getName()] = $this->makePropertyFromReflection($metadata, $property);
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

        if (!$propertyMetadata->choices && isset($propertyMetadata->list['entity'], $propertyMetadata->list['value'], $propertyMetadata->list['title'])) {
            $propertyMetadata->choices = $this->loadChoices($propertyMetadata->list['entity'], $propertyMetadata->list['value'], $propertyMetadata->list['title']);
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
     * Get type of property from property declaration
     *
     * @link http://stackoverflow.com/a/34340504
     *
     * @param \ReflectionProperty $property
     *
     * @return null|string
     */
    protected function getPhpDocPropertyType(\ReflectionProperty $property)
    {
        $doc = $property->getDocComment();
        preg_match_all('#@(.*?)\n#s', $doc, $annotations);
        if (isset($annotations[1])) {
            foreach ($annotations[1] as $annotation) {
                preg_match_all('/\s*(.*?)\s+(\S*)/s', $annotation, $parts);
                if (!isset($parts[1][0], $parts[2][0])) {
                    continue;
                }
                $declaration = $parts[1][0];
                $type = $parts[2][0];
                if ($declaration === 'var') {
                    if (substr($type, 0, 1) === '$') {
                        return null;
                    }
                    else {
                        return $type;
                    }
                }
            }
            return null;
        }
        return $doc;
    }

    /**
     * @param array $properties
     * @return array
     */
    protected function makeJoins(array $properties)
    {
        $result = [];

        foreach ($properties as $property) {
            $type = $this->getPhpDocPropertyType($property);
            if (!($type && class_exists($type))) {
                continue;
            }
            $entityMetadata = $this->reader->getClassAnnotation(
                new \ReflectionClass($type),
                Entity::CLASSNAME
            );
            if ($entityMetadata) {
                $result[$type] = $property->getName();
            }
        }

        return $result;
    }
}
