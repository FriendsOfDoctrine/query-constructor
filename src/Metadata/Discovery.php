<?php

namespace Informika\QueryConstructor\Metadata;

use Informika\QueryConstructor\Mapping\Reader;

/**
 * Metadata discovery service
 *
 * @author Nikita Pushkov
 */
class Discovery
{
    /**
     * @var string
     */
    protected $root;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var array (namespace => path)
     */
    protected $lookupPaths = [];

    /**
     * Constructor
     *
     * @param string $root
     * @param Reader $reader
     */
    public function __construct($root, Reader $reader)
    {
        $this->reader = $reader;
        $this->root = $root;
    }

    /**
     * @param string $namespace
     * @param string $path
     */
    public function registerLookupPath($namespace, $path)
    {
        $this->lookupPaths[$namespace] = $path;
    }

    /**
     * @return \Informika\QueryConstructor\Mapping\ClassMetadata[]
     */
    public function discoverAll()
    {
        $result = [];
        foreach ($this->lookupPaths as $namespace => $path) {
            $result += $this->discover($namespace, $this->root . $path);
        }
        return $result;
    }

    /**
     * @return \Informika\QueryConstructor\Mapping\ClassMetadata[]
     */
    public function discover($namespace, $path)
    {
        $metadata = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
            \RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
        );

        foreach ($iterator as $item) {
            if (!$item->isFile() || $item->getExtension() !== 'php') {
                continue;
            }

            $className = $namespace . $this->getClassByPath($path, $item);

            $classMetadata = $this->reader->getClassMetaData($className);
            if ($classMetadata) {
                $metadata[$className] = $classMetadata;
            }
        }

        return $metadata;
    }

    /**
     * @param string $path
     * @param \SplFileInfo $file
     * @return string
     */
    protected function getClassByPath($path, \SplFileInfo $file)
    {
        $localNamespace = str_replace(
            [$path, '/'],
            ['', '\\'],
            $file->getPath()
        );
        return $localNamespace . '\\' . $file->getBasename('.php');
    }
}
