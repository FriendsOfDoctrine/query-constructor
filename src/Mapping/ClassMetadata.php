<?php

namespace FOD\QueryConstructor\Mapping;

use FOD\QueryConstructor\Mapping\Annotation\Entity;
use FOD\QueryConstructor\Mapping\Annotation\Property;

/**
 * Metadata for Query Constructor
 *
 * @author Nikita Pushkov
 */
class ClassMetadata
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @var Entity
     */
    protected $entity;

    /**
     * @var array
     */
    protected $joins;

    /**
     * @var Property[]
     */
    protected $aggregatableProperties;

    /**
     * @var Property[]
     */
    protected $properties;

    /**
     * @param Entity $entity
     */
    public function __construct($className, Entity $entity)
    {
        $this->className = $className;
        $this->entity = $entity;
        if (!$entity->getTitle()) {
            $entity->setTitle(ucfirst($this->getClassBaseName($className)));
        }
    }

    /**
     * @return Entity
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return string
     */
    public function getEntityClass()
    {
        return $this->className;
    }

    /**
     * @return Property[]
     */
    public function getAggregatableProperties()
    {
        return $this->aggregatableProperties;
    }

    /**
     * @param array $properties
     */
    public function setAggregatableProperties(array $properties)
    {
        $this->aggregatableProperties = $properties;
    }

    /**
     * @return Property[]
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
    }

    /**
     * @return Property[]
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * @param array $joins
     */
    public function setJoins(array $joins)
    {
        $this->joins = $joins;
    }

    /**
     * @param string $className
     * @return string
     */
    protected function getClassBaseName($className)
    {
        $lastNamespacePos = strrpos($className, '\\');

        return false === $lastNamespacePos ? $className : substr($className, $lastNamespacePos + 1);
    }
}
