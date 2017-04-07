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
    protected $select = [];

    /**
     * @var array
     */
    protected $selectExcept = [];

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
        if (array_key_exists('select', $values)) {
            $this->select = (array) $values['select'];
        }
        if (array_key_exists('select_except', $values)) {
            $this->selectExcept = (array) $values['select_except'];
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
    public function getSelect()
    {
        return $this->select;
    }

    /**
     * @return array
     */
    public function getSelectExcept()
    {
        return $this->selectExcept;
    }
}
