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
    const CLASSNAME = __CLASS__;

    /**
     * @Required
     *
     * @var string
     */
    public $label;

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }
}
