<?php

namespace Informika\QueryConstructor\Serializer;

/**
 * Simple PropertyAccessor
 * @author Nikita Pushkov
 */
class PropertyAccessor
{
    /**
     * @var object
     */
    protected $object;

    /**
     * @var \ReflectionObject
     */
    protected $reflection;

    /**
     * PropertyAccessor Constructor
     *
     * @param object $object
     *
     * @throws \LogicException
     */
    public function __construct($object)
    {
        if (!is_object($object)) {
            throw new \LogicException('Object required');
        }

        $this->reflection = new \ReflectionObject($object);
        $this->object = $object;
    }

    /**
     * @return object
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @throws \LogicException
     * @throws \BadMethodCallException
     */
    public function setValue(string $name, $value)
    {
        if ($mutator = $this->getPublicProperty($name)) {
            $this->object->{$mutator} = $value;
        } elseif ($mutator = $this->getPublicMethod($name, 'set')) {
            $this->object->{$mutator}($value);
        } else {
            throw new \BadMethodCallException(sprintf(
                'Class "%s" has neither property nor setter "%s"',
                get_class($this->object),
                $name
            ));
        }
    }

    /**
     * @param string $name
     *
     * @return mixed
     * @throws \LogicException
     * @throws \BadMethodCallException
     */
    public function getValue(string $name)
    {
        if ($accessor = $this->getPublicProperty($name)) {
            return $this->object->{$accessor};
        } elseif ($accessor = $this->getFirstPublicMethod($name, ['get', 'is', 'has'])) {
            return $this->object->{$accessor}();
        } else {
            throw new \BadMethodCallException(sprintf(
                'Class "%s" has neither property nor accessor "%s"',
                get_class($this->object),
                $name
            ));
        }
    }

    /**
     * @param string $name
     *
     * @return string|null
     */
    protected function getPublicProperty(string $name)
    {
        try {
            $property = $this->reflection->getProperty($name);
            return $property->isPublic() ? $name : null;
        } catch (\ReflectionException $ex) {
            return null;
        }
    }

    /**
     * @param string $property
     * @param string $prefix
     *
     * @return string|null
     */
    protected function getPublicMethod(string $property, string $prefix)
    {
        $name = $prefix . ucfirst($property);
        try {
            $method = $this->reflection->getMethod($name);
            return $method->isPublic() ? $name : null;
        } catch (\ReflectionException $ex) {
            return null;
        }
    }

    /**
     * @param string $property
     * @param array $prefixes
     *
     * @return string|null
     */
    protected function getFirstPublicMethod(string $property, array $prefixes)
    {
        foreach ($prefixes as $prefix) {
            if ($method = $this->getPublicMethod($property, $prefix)) {
                return $method;
            }
        }
        return null;
    }
}
