<?php

namespace Informika\QueryConstructor\Metadata;

use Informika\QueryConstructor\Mapping\Reader;
use Informika\QueryConstructor\Metadata\Discovery;

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
    protected $entities;

    /**
     * @var Discovery
     */
    protected $dicsovery;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @param Discovery $discovery
     * @param Reader $reader
     */
    public function __construct(Discovery $discovery, Reader $reader)
    {
        $this->discovery = $discovery;
        $this->reader = $reader;
    }

    /**
     * @return array
     */
    public function getEntityLabels()
    {
        $ret = [];
        foreach ($this->getEntities() as $entity => $metaData) {
            $ret[$entity] = $metaData->getEntity()->getLabel();
        }

        asort($ret);

        return $ret;
    }

    /**
     * @return array
     */
    public function getEntities()
    {
        if (is_null($this->entities)) {
            $this->entitites = $this->discovery->discoverAll();
        }
        return $this->entitites;
    }
}
