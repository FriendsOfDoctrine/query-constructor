<?php

namespace FOD\QueryConstructor\Metadata;

use Doctrine\Common\Annotations\Reader as AnnotationReader;
use Doctrine\ORM\EntityManager;
use FOD\QueryConstructor\Mapping\ClassMetadata;
use FOD\QueryConstructor\Mapping\Reader;
use FOD\QueryConstructor\Metadata\DoctrineDiscovery;

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
    protected $metadataRegistry;

    /**
     * @var DoctrineDiscovery
     */
    protected $dicsovery;

    /**
     * @param EntityManager $em
     * @param AnnotationReader $reader
     */
    public function __construct(EntityManager $em, AnnotationReader $reader)
    {
        $this->discovery = new DoctrineDiscovery($em, $reader);
    }

    /**
     * @return array
     */
    public function getEntityTitles()
    {
        $ret = [];
        foreach ($this->getMetadataRegistry() as $entity => $metaData) {
            $ret[$entity] = $metaData->getEntity()->getTitle();
        }

        asort($ret);

        return $ret;
    }

    /**
     * @return ClassMetadata
     */
    public function getMetadata($className)
    {
        if (empty($this->metadataRegistry[$className])) {
            $classMetadata = $this->discovery->getClassMetaData($className);
            if (!$classMetadata) {
                return null;
            }
            $this->metadataRegistry[$className] = $classMetadata;
        }
        return $this->metadataRegistry[$className];
    }

    /**
     * @return array
     */
    public function getMetadataRegistry()
    {
        if (is_null($this->metadataRegistry)) {
            $this->metadataRegistry = $this->discovery->discoverAll();
        }
        return $this->metadataRegistry;
    }

    /**
     * @param string $className
     *
     * @return array
     * @throws \LogicException
     */
    public function get($className)
    {
        $entity = $this->getMetadata($className);
        if (!$entity) {
            throw new \LogicException('Entity [' . $className . '] is not mapped as Constructor Entity');
        }

        return [
            'aggregatableProperties' => $this->getAggregatablePropertyTitles($entity),
            'properties' => $this->getProperties($entity),
            'joinableEntities' => $this->getJoins($entity),
        ];
    }

    /**
     * @param ClassMetadata $entity
     *
     * @return array
     */
    public function getAggregatablePropertyTitles(ClassMetadata $entity)
    {
        $ret = [];
        foreach ($entity->getAggregatableProperties() as $property => $metaData) {
            $ret[$property] = $metaData->getTitle();
        }

        asort($ret);

        return $ret;
    }

    /**
     * @param ClassMetadata $entity
     *
     * @return array
     */
    public function getProperties(ClassMetadata $entity)
    {
        $ret = $entity->getProperties();

        asort($ret);

        return $ret;
    }

    /**
     * @param ClassMetadata $entity
     *
     * @return array
     */
    public function getJoins(ClassMetadata $entity)
    {
        $ret = array_reduce(
            array_keys($entity->getJoins()),
            function (array $entityMap, $entityClass) {
                $entityMap[$entityClass] = $this->getMetadata($entityClass)->getEntity()->getTitle();
                return $entityMap;
            },
            []
        );

        asort($ret);

        return $ret;
    }
}
