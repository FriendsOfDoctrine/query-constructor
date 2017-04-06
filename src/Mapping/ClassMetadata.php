<?php

namespace Informika\QueryConstructor\Mapping;

use Informika\QueryConstructor\Mapping\Annotation\Entity;

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
}
