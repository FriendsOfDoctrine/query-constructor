<?php
namespace Informika\QueryConstructor\MetaDataProvider;

use Doctrine\ORM\QueryBuilder;

/**
 * @author Sergey Koshkarov
 */
class ProviderRegistry
{
    /**
     * @var array|ProviderInterface[]
     */
    protected $entities = [];

    /**
     * @param ProviderInterface $metaDataProvider
     */
    public function register(ProviderInterface $metaDataProvider)
    {
        if(isset($this->entities[$metaDataProvider->getEntityClass()])){
            throw new \LogicException('Meta data provider for entity ['.$metaDataProvider->getEntityClass().'] already registered');
        }
        $this->entities[$metaDataProvider->getEntityClass()] = $metaDataProvider;
    }

    /**
     * @return array
     */
    public function getRegisteredEntities()
    {
        $ret = [];
        foreach ($this->entities as $entity => $constructorHelperEntity) {
            $ret[$entity] = $constructorHelperEntity->getEntityTitle();
        }

        return $ret;
    }

    /**
     * @param string $entity
     *
     * @return array
     * @throws \LogicException
     */
    public function get($entity)
    {
        if (!isset($this->entities[$entity])) {
            throw new \LogicException('Entity [' . $entity . '] not registered');
        }

        return [
            'aggregatableProperties' => $this->entities[$entity]->getAggregatableProperties(),
            'properties' => $this->entities[$entity]->getProperties(),
            'joinableEntities' => array_reduce(
                array_keys($this->entities[$entity]->getJoinableEntities()),
                function (array $entityMap, $entityClass) {
                    $entityMap[$entityClass] = $this->entities[$entityClass]->getEntityTitle();
                    return $entityMap;
                },
                []
            ),
        ];
    }

    /**
     * @param string $entity
     *
     * @return ProviderInterface
     * @throws \LogicException
     */
    public function getProvider($entity)
    {
        if (!isset($this->entities[$entity])) {
            throw new \LogicException('Entity [' . $entity . '] not registered');
        }

        return $this->entities[$entity];
    }

    /**
     * @param QueryBuilder $qb
     * @param string $entityClass
     * @param string $entityAlias
     * @param \DateTime $dateReport
     */
    public function onQueryCreated(QueryBuilder $qb, $entityClass, $entityAlias, \DateTime $dateReport)
    {
        if (!isset($this->entities[$entityClass])) {
            throw new \LogicException('Entity [' . $entityClass . '] not registered');
        }

        $this->entities[$entityClass]->onQueryCreated($qb, $entityAlias, $dateReport);
    }
}
