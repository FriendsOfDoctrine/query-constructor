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
    public $title;

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }
}
