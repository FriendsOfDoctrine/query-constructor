<?php

namespace Informika\QueryConstructor\Mapping\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 */
class Property
{
    /**
     * @const string
     */
    const CLASSNAME = __CLASS__; // PHP5.4 support

    /**
     * @const string
     */
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_DATE = 'date';
    const TYPE_SINGLE_CHOICE = 'single_choice';
    const TYPE_MULTIPLE_CHOICE = 'multiple_choice';

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $type;

    /**
     * @var array (entity, value, title)
     */
    public $list;

    /**
     * @var array
     */
    public $choices;

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
