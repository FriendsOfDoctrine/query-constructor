<?php

namespace Informika\QueryConstructor\Mapping;

use Doctrine\Common\Annotations\Reader as AnnotationReader;

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
        $entityMetadata = $this->reader->getClassAnnotation(
            new \ReflectionClass($className),
            Annotation\Entity::CLASSNAME
        );
        if ($entityMetadata) {
            $classMetadata = new ClassMetadata($entityMetadata);

            return $classMetadata;
        }

        return null;
    }
}
