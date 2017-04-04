<?php

namespace Informika\QueryConstructor\MetaDataProvider;

use Doctrine\ORM\QueryBuilder;

/**
 * @author Sergey Koshkarov
 */
interface ProviderInterface
{
    /**
     * @const string
     */
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_DATE = 'date';
    const TYPE_SINGLE_CHOICE = 'single_choice';
    const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    /**
     * Указывается массив полей, которые могут агрегироваться (ключ - имя поля, значение - название)
     * Это поле попадет в SELECT aggregateFunction(field_name)
     *
     * ['property' => 'label']
     *
     * @return array
     */
    public function getAggregatableProperties();

    /**
     * ['field' =>
     *        [
     *          'title' => 'title',
     *          'type' => 'type',
     *          'choices' =>
     *                  [
     *                      'choice_id' => 'choice 1',
     *                      'choice_id' => 'choice 2'
     *                  ]
     *        ]
     * ]
     *
     * @return array
     */
    public function getProperties();

    /**
     * Связи, которые можно запрашивать в условиях 
     *
     * [
     *  'entityClass1' => 'referenceField',
     *  'entityClass2' => ['entityClass1', 'entityClass2'] // В провайдере entityClass1 нужно также определить entityClass2 с referenceField
     * ]
     *
     * @return array
     */
    public function getJoinableEntities();

    /**
     * @return string
     */
    public function getEntityClass();

    /**
     * @return string
     */
    public function getEntityTitle();

    /**
     * Callback after query created
     *
     * @param QueryBuilder $qb
     * @param string $entitySelectAlias
     * @param \DateTime $dateReport
     */
    public function onQueryCreated(QueryBuilder $qb, string $entitySelectAlias, \DateTime $dateReport);

}
