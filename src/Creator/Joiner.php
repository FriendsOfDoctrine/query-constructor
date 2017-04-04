<?php

namespace Informika\QueryConstructor\Creator;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Informika\QueryConstructor\MetaDataProvider\ProviderRegistry;
use Informika\QueryConstructor\MetaDataProvider\ProviderInterface;

/**
 * @author Nikita Pushkov
 */
class Joiner
{
    /**
     * @var ProviderInterface
     */
    protected $baseEntityMetadataProvider;

    /**
     * @var EntityManagerInterfaceInterface
     */
    protected $em;

    /**
     * @var ProviderRegistry
     */
    protected $metadataHelper;

    /**
     * @param EntityManagerInterface $em
     * @param ProviderRegistry $metadataHelper
     */
    public function __construct(EntityManagerInterface $em, ProviderRegistry $metadataHelper)
    {
        $this->em = $em;
        $this->metadataHelper = $metadataHelper;
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
        $this->baseEntityMetadataProvider = $this->metadataHelper->getProvider($entityClass);
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

        if (!array_key_exists($entityClass, $this->baseEntityMetadataProvider->getJoinableEntities())) {
            throw new \LogicException(sprintf(
                'Could not join entity [%s] to [%s]. Set up this relation in the provider [%s].',
                $entityClass,
                $this->baseEntityMetadataProvider->getEntityClass(),
                get_class($this->baseEntityMetadataProvider)
            ));
        }
        if (!$dateReport) {
            $dateReport = new \DateTime();
        }

        $relation = $this->baseEntityMetadataProvider->getJoinableEntities()[$entityClass];

        if (is_array($relation)) {
            array_reduce($relation, function (
                ProviderInterface $provider,
                $entityClass
            ) use ($qb, $dateReport) {
                $relation = $provider->getJoinableEntities()[$entityClass];
                if (is_array($relation)) {
                    throw \LogicException('Recursion not supported');
                }

                $this->makeJoin(
                    $qb,
                    $entityClass,
                    $this->getEntityAlias($provider->getEntityClass()),
                    $relation,
                    $dateReport
                );

                return $this->metadataHelper->getProvider($entityClass);
            }, $this->baseEntityMetadataProvider);
            $entityAlias = $this->getEntityAlias($entityClass);
        } else {
            $entityAlias = $this->makeJoin(
                $qb,
                $entityClass,
                $this->getEntityAlias($this->baseEntityMetadataProvider->getEntityClass()),
                $relation,
                $dateReport
            );
        }

        return $entityAlias;
    }

    /**
     * @param QueryBuilder $qb
     * @param string $entityClass
     * @param string $entityBaseAlias
     * @param string $referenceKey
     * @param \DateTime $dateReport
     *
     * @return string
     */
    protected function makeJoin(
        QueryBuilder $qb,
        $entityClass,
        $entityBaseAlias,
        $referenceKey,
        \DateTime $dateReport
    )
    {
        $entityAlias = $this->getEntityAlias($entityClass);

        if ($this->alreadyJoined($qb, $entityAlias)) {
            return $entityAlias;
        }

        $entityMetaData = $this->em->getClassMetadata($entityClass);
        $id = $entityMetaData->getIdentifier();

        $qb->join(
            $entityClass,
            $entityAlias,
            Join::WITH,
            sprintf(
                '%1$s.%2$s = %3$s.%4$s AND (:joinReportDate BETWEEN %3$s.fromDate AND %3$s.toDate)',
                $entityBaseAlias,
                $referenceKey,
                $entityAlias,
                $id[0]
            )
        );
        if (!$qb->getParameter('joinReportDate')) {
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
        return in_array($entityAlias, $qb->getAllAliases());
    }
}
