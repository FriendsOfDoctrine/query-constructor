<?php

namespace Informika\QueryConstructor\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Entity
{
    /**
     * @const string
     */
    const CLASSNAME = __CLASS__; // PHP5.4 support

    /**
     * @Required
     *
     * @var string
     */
    protected $title;

    /**
     * @var array
     */
    protected $aggregatableFields = [];

    /**
     * @var array
     */
    protected $aggregatableFieldsExcept = [];

    /**
     * @var array
     */
    protected $filterableFields = [];

    /**
     * @var array
     */
    protected $filterableFieldsExcept = [];

    /**
     * @var array
     */
    protected $dateBetween = [];

    /**
     * Constructor
     *
     * @param array $values
     */
    public function __construct(array $values)
    {
        if (array_key_exists('title', $values)) {
            $this->title = $values['title'];
        }
        if (array_key_exists('aggregatable_fields', $values)) {
            $this->aggregatableFields = (array) $values['aggregatable_fields'];
        }
        if (array_key_exists('aggregatable_fields_except', $values)) {
            $this->aggregatableFieldsExcept = (array) $values['aggregatable_fields_except'];
        }
        if (array_key_exists('filterable_fields', $values)) {
            $this->filterableFields = (array) $values['filterable_fields'];
        }
        if (array_key_exists('filterable_fields_except', $values)) {
            $this->filterableFieldsExcept = (array) $values['filterable_fields_except'];
        }
        if (array_key_exists('date_between', $values) && is_array($values['date_between'])) {
            if (count($values['date_between']) !== 2) {
                throw new \LogicException('date_between must be an array containing exactly 2 column names');
            }
            $this->dateBetween = $values['date_between'];
        }
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return array
     */
    public function getAggregatableFields()
    {
        return $this->aggregatableFields;
    }

    /**
     * @return array
     */
    public function getAggregatableFieldsExcept()
    {
        return $this->aggregatableFieldsExcept;
    }

    /**
     * @return array
     */
    public function getFilterableFields()
    {
        return $this->filterableFields;
    }

    /**
     * @return array
     */
    public function getFilterableFieldsExcept()
    {
        return $this->filterableFieldsExcept;
    }

    /**
     * @return array
     */
    public function getDateBetween()
    {
        return $this->dateBetween;
    }
}
