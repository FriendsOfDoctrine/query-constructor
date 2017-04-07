<?php

namespace Informika\QueryConstructor\Mapping;

use Informika\QueryConstructor\Mapping\Annotation\Entity;
use Informika\QueryConstructor\Mapping\Annotation\Property;

/**
 * Metadata for Query Constructor
 *
 * @author Nikita Pushkov
 */
class ClassMetadata
{
    /**
     * @var Entity
     */
    protected $entity;

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
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return Table
     */
    public function getEntity()
    {
        return $this->entity;
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
}
