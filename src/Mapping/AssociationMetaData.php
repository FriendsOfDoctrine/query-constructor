<?php
namespace FOD\QueryConstructor\Mapping;

use FOD\QueryConstructor\Mapping\Annotation\Entity;

/**
 * Class AssociationMetaData
 * @package FOD\QueryConstructor\Mapping
 */
class AssociationMetaData
{
    /** @var  Entity */
    protected $entityMetadata;
    /** @var  string */
    protected $targetEntity;
    /** @var AssociationMetaData[] */
    protected $assotiationWith = [];

    /**
     * AssociationMetaData constructor.
     *
     * @param $targetEntity
     * @param Entity $entityMetadata
     * @param array $associationWith
     */
    public function __construct($targetEntity, Entity $entityMetadata, array $associationWith = [])
    {
        $this->targetEntity = $targetEntity;
        $this->entityMetadata = $entityMetadata;
        $this->assotiationWith = $associationWith;
    }

    /**
     * @return Entity
     */
    public function getEntityMetadata()
    {
        return $this->entityMetadata;
    }

    /**
     * @return string
     */
    public function getTargetEntity()
    {
        return $this->targetEntity;
    }

    /**
     * @return AssociationMetaData[]
     */
    public function getAssotiationWith()
    {
        return $this->assotiationWith;
    }
}