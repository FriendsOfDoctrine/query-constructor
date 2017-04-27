<?php

namespace FOD\QueryConstructor\Creator;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use FOD\QueryConstructor\Metadata\Registry;
use FOD\QueryConstructor\Mapping\ClassMetadata;

/**
 * @author Nikita Pushkov
 */
class Joiner
{
    /**
     * @var ClassMetadata
     */
    protected $baseEntityMetadataProvider;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param EntityManagerInterface $em
     * @param Registry $registry
     */
    public function __construct(EntityManagerInterface $em, Registry $registry)
    {
        $this->em = $em;
        $this->registry = $registry;
    }

    /**
     * @param string $entityClass
     *
     * @return string
     */
    public function getEntityAlias($entityClass)
    {
        return substr(strrchr($entityClass, "\\"), 1);
    }

    /**
     * @param string $entityClass
     */
    public function setBaseEntity($entityClass)
    {
        $this->baseEntityMetadataProvider = $this->registry->getMetadata($entityClass);
    }

    /**
     * @param QueryBuilder $qb
     * @param string $entityClass
     * @param \DateTime $dateReport
     *
     * @return string
     * @throws \LogicException
     */
    public function join(QueryBuilder $qb, $entityClass, \DateTime $dateReport = null)
    {
        if (!$this->baseEntityMetadataProvider) {
            throw new \LogicException('BaseEntityMetadataProvider is not set. Call setBaseEntity() method first.');
        }

        if (!array_key_exists($entityClass, $this->baseEntityMetadataProvider->getJoins())) {
            throw new \LogicException(sprintf(
                'Could not join entity [%s] to [%s]. Set up this relation in the provider [%s].',
                $entityClass,
                $this->baseEntityMetadataProvider->getEntityClass(),
                get_class($this->baseEntityMetadataProvider)
            ));
        }

        /** @var string $propertyJoin */
        $propertyJoin = $this->baseEntityMetadataProvider->getJoins()[$entityClass];

        $crossingJoins = $this->getCrossingJoins(current($qb->getRootEntities()), array_flip($this->baseEntityMetadataProvider->getJoins())[$propertyJoin], $propertyJoin);
        foreach ($crossingJoins as $crossingJoin) {
            $this->join($qb, $crossingJoin, $dateReport);
        }

        if (!$dateReport) {
            $dateReport = new \DateTime();
        }

        $relation = $this->baseEntityMetadataProvider->getJoins()[$entityClass];

        if (is_array($relation)) {
            array_reduce($relation, function (
                ClassMetadata $provider,
                $entityClass
            ) use ($qb, $dateReport) {
                $relation = $provider->getJoins()[$entityClass];
                if (is_array($relation)) {
                    throw \LogicException('Recursion not supported');
                }

                $this->makeJoin(
                    $qb,
                    $entityClass,
                    $this->getEntityAlias($provider->getEntityClass()),
                    $relation,
                    $provider->getEntity()->getDateBetween(),
                    $dateReport
                );

                return $this->registry->getMetadata($entityClass);
            }, $this->baseEntityMetadataProvider);
            $entityAlias = $this->getEntityAlias($entityClass);
        } else {
            $entityAlias = $this->makeJoin(
                $qb,
                $entityClass,
                $this->getEntityAlias($crossingJoins ? end($crossingJoins) : $this->baseEntityMetadataProvider->getEntityClass()),
                $relation,
                $this->baseEntityMetadataProvider->getEntity()->getDateBetween(),
                $dateReport
            );
        }

        return $entityAlias;
    }

    /**
     * @param $targetClass
     * @param $dstClass
     * @param $dstProp
     *
     * @return array|mixed
     */
    protected function getCrossingJoins($targetClass, $dstClass, $dstProp)
    {
        $joins = [];

        $ormMetadata = $this->em->getClassMetadata($targetClass);

        foreach ($ormMetadata->associationMappings as $mapping) {
            if ($mapping['targetEntity'] !== $dstClass) {
                $dstOrmMetadata = $this->em->getClassMetadata($mapping['targetEntity']);
                $joins[] = [$mapping['targetEntity']];
                if (!isset($dstOrmMetadata->associationMappings[$dstProp])) {
                    $joins[] = $this->getCrossingJoins($mapping['targetEntity'], $dstClass, $dstProp);
                }
            }
        }

        return $joins ? call_user_func_array('array_merge', $joins) : [];
    }

    /**
     * @param QueryBuilder $qb
     * @param string $entityClass
     * @param string $entityBaseAlias
     * @param string $referenceKey
     * @param array $dateBetween
     * @param \DateTime $dateReport
     *
     * @return string
     */
    protected function makeJoin(
        QueryBuilder $qb,
        $entityClass,
        $entityBaseAlias,
        $referenceKey,
        array $dateBetween,
        \DateTime $dateReport
    )
    {
        $entityAlias = $this->getEntityAlias($entityClass);

        if ($this->alreadyJoined($qb, $entityAlias)) {
            return $entityAlias;
        }

        $entityMetaData = $this->em->getClassMetadata($entityClass);
        $id = $entityMetaData->getIdentifier();

        $withCondition = $dateBetween
            ? sprintf(
                '%1$s.%2$s = %3$s.%4$s AND (:joinReportDate BETWEEN %3$s.%5$s AND %3$s.%6$s)',
                $entityBaseAlias,
                $referenceKey,
                $entityAlias,
                $id[0],
                $dateBetween[0],
                $dateBetween[1]
            )
            : sprintf(
                '%1$s.%2$s = %3$s.%4$s',
                $entityBaseAlias,
                $referenceKey,
                $entityAlias,
                $id[0]
            );

        $qb->join($entityClass, $entityAlias, Join::WITH, $withCondition);
        if ($dateBetween && !$qb->getParameter('joinReportDate')) {
            $qb->setParameter(':joinReportDate', $dateReport, Type::DATETIME);
        }
        return $entityAlias;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $entityAlias
     *
     * @return bool
     */
    protected function alreadyJoined(QueryBuilder $qb, $entityAlias)
    {
        return in_array($entityAlias, $qb->getAllAliases(), true);
    }
}
