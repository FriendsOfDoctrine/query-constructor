<?php

namespace FOD\QueryConstructor\Metadata;

use Doctrine\Common\Annotations\Reader as AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use FOD\QueryConstructor\Mapping\Reader;

/**
 * Description of DoctrineDiscovery
 *
 * @author Nikita Pushkov
 */
class DoctrineDiscovery
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @param EntityManager $em
     * @param AnnotationReader $reader
     */
    public function __construct(EntityManager $em, AnnotationReader $reader)
    {
        $this->em = $em;
        $this->reader = new Reader($em, $reader);
    }

    /**
     * @return array
     */
    public function discoverAll()
    {
        $metadata = [];

        /** @var ClassMetadata $classMetadata */
        foreach ($this->em->getMetadataFactory()->getAllMetadata() as $ormClassMetadata) {
            $classMetadata = $this->reader->getClassMetaData($ormClassMetadata);
            if ($classMetadata) {
                $metadata[$ormClassMetadata->getName()] = $classMetadata;
            }
        }

        return $metadata;
    }

    /**
     * @param string $className
     * @return \FOD\QueryConstructor\Mapping\ClassMetadata
     */
    public function getClassMetaData($className)
    {
        $ormClassMetadata = $this->em->getMetadataFactory()->getMetadataFor($className);

        return $this->reader->getClassMetaData($ormClassMetadata);
    }
}
