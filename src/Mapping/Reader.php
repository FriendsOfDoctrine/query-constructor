<?php

namespace Informika\QueryConstructor\Mapping;

use Doctrine\Common\Annotations\Reader as AnnotationReader;
use Informika\QueryConstructor\Mapping\Annotation\Entity;
use Informika\QueryConstructor\Mapping\Annotation\Property;

/**
 * @author Nikita Pushkov
 */
class Reader
{
    /**
     * @var AnnotationReader
     */
    protected $reader;

    /**
     * Constructor
     *
     * @param AnnotationReader $reader
     */
    public function __construct(AnnotationReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param string $className
     * @return ClassMetadata|null
     */
    public function getClassMetaData($className)
    {
        $reflection = new \ReflectionClass($className);
        $entityMetadata = $this->reader->getClassAnnotation(
            $reflection,
            Entity::CLASSNAME
        );
        if (!$entityMetadata) {
            return null;
        }
        $classMetadata = new ClassMetadata($entityMetadata);
        $classMetadata->setProperties($this->fetchProperties($reflection));

        return $classMetadata;
    }

    /**
     * @param \ReflectionClass $reflection
     * @return array
     */
    protected function fetchProperties(\ReflectionClass $reflection)
    {
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PRIVATE);
        $result = [];
        foreach ($properties as $property) {
            $result[$property->getName()] = $this->makePropertyFromReflection($property);
        }

        return $result;
    }

    /**
     * @param \ReflectionProperty $property
     * @return Property
     */
    protected function makePropertyFromReflection(\ReflectionProperty $property)
    {
        $propertyMetadata = new Property();
        $propertyMetadata->title = ucfirst($property->getName());
        $propertyMetadata->type = Property::TYPE_STRING;

        return $propertyMetadata;
    }
}
