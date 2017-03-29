<?php

namespace Informika\QueryConstructor\Serializer;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * QueryBuilder serializer
 *
 * @author Nikita Pushkov
 */
class Serializer
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param QueryBuilder $queryBuilder
     *
     * @return string
     */
    public function serialize(QueryBuilder $queryBuilder): string
    {
        $queryBuilderAccessor = new PropertyAccessor($queryBuilder);
        $data = [
            'dqlParts' => array_filter($queryBuilder->getDQLParts()),
            'attributes' => array_reduce(
                $this->getSerializableAttributeNames(),
                function (array $attributes, $attribute) use ($queryBuilderAccessor) {
                    $attributes[$attribute] = $queryBuilderAccessor->getValue($attribute);
                    return $attributes;
                },
                []
            ),
        ];

        return serialize($data);
    }

    /**
     * @param string $serialized
     *
     * @return QueryBuilder
     */
    public function unserialize(string $serialized): QueryBuilder
    {
        $result = unserialize($serialized);

        $queryBuilder = $this->em->createQueryBuilder();
        foreach ($result['dqlParts'] as $name => $part) {
            $queryBuilder->add($name, $part);
        }

        $queryBuilderAccessor = new PropertyAccessor($queryBuilder);
        foreach ($result['attributes'] as $name => $value) {
            $queryBuilderAccessor->setValue($name, $value);
        }

        return $queryBuilder;
    }

    /**
     * @return array
     */
    protected function getSerializableAttributeNames(): array
    {
        // dqlParts has explicit access
        return [
            'parameters',
            'firstResult',
            'maxResults',
            'lifetime',
            'cacheMode',
            'cacheable',
            'cacheRegion',
        ];
    }
}
