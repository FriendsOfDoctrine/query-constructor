<?php

namespace Informika\QueryConstructor\Metadata;

use Informika\QueryConstructor\Mapping\ClassMetadata;
use Informika\QueryConstructor\Mapping\Reader;
use Informika\QueryConstructor\Metadata\Discovery;

/**
 * Metadata Registry
 *
 * @author Nikita Pushkov
 */
class Registry
{
    /**
     * @var array
     */
    protected $entities;

    /**
     * @var Discovery
     */
    protected $dicsovery;

    /**
     * @param Discovery $discovery
     * @param Reader $reader
     */
    public function __construct(Discovery $discovery)
    {
        $this->discovery = $discovery;
    }

    /**
     * @return array
     */
    public function getEntityTitles()
    {
        $ret = [];
        foreach ($this->getEntities() as $entity => $metaData) {
            $ret[$entity] = $metaData->getEntity()->getTitle();
        }

        asort($ret);

        return $ret;
    }

    /**
     * @return array
     */
    public function getEntity($className)
    {
        if (empty($this->entitites[$className])) {
            $classMetadata = $this->discovery->getClassMetaData($className);
            if (!$classMetadata) {
                return null;
            }
            $this->entitites[$className] = $classMetadata;
        }
        return $this->entitites[$className];
    }

    /**
     * @return array
     */
    public function getEntities()
    {
        if (is_null($this->entities)) {
            $this->entitites = $this->discovery->discoverAll();
        }
        return $this->entitites;
    }

    /**
     * @param string $className
     *
     * @return array
     * @throws \LogicException
     */
    public function get($className)
    {
        $entity = $this->getEntity($className);
        if (!$entity) {
            throw new \LogicException('Entity [' . $className . '] is not mapped as Constructor Entity');
        }

        return [
            'aggregatableProperties' => $this->getPropertyTitles($entity),
            'properties' => $entity->getProperties(),
            'joinableEntities' => [],
        ];
    }

    /**
     * @return array
     */
    public function getPropertyTitles(ClassMetadata $entity)
    {
        $ret = [];
        foreach ($entity->getProperties() as $property => $metaData) {
            $ret[$property] = $metaData->getTitle();
        }

        asort($ret);

        return $ret;
    }
}
