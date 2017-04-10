<?php

namespace Informika\QueryConstructor\Bundle\DependencyInjection;


use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Exception\InvalidDefinitionException;

class QueryConstructorExtension extends Extension
{
    /**
     * @param array $configs
     * @param ContainerBuilder $container
     *
     * @throws InvalidDefinitionException
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
        $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yml');

        // always first check if the primary service is defined
        if (!$container->has('query_constructor.registry')) {
            return;
        }

        $discoveryDefinition = $container->findDefinition('query_constructor.discovery');

        foreach ($configs as $subConfig) {
            if (!isset($subConfig['discovery'])) {
                continue;
            }
            $discoveryConfig = $subConfig['discovery'];
            if (!$this->validateDiscoveryConfiguration($discoveryConfig)) {
                throw new InvalidDefinitionException('Config must be an array of format ["Namespace", "Path/To/Folder"] or an array of these arrays');
            }
            if (count($discoveryConfig) && is_string($discoveryConfig[0]) && is_string($discoveryConfig[1])) {
                $discoveryConfig = [$discoveryConfig];
            }

            foreach($discoveryConfig as $lookupPath) {
                $discoveryDefinition->addMethodCall('registerLookupPath', $lookupPath);
            }
        }
    }

    /**
     * @param array $config
     * @return boolean
     */
    protected function validateDiscoveryConfiguration($config)
    {
        if (!is_array($config)) {
            return false;
        }

        if (count($config) == 2) {
            if (is_string($config[0]) && is_string($config[1])) {
                return true;
            }
        }

        foreach ($config as $rowConfig) {
            if (!is_array($rowConfig)) {
                return false;
            }

            if (!(count($rowConfig) === 2 && is_string($rowConfig[0]) && is_string($rowConfig[1]))) {
                return false;
            }
        }

        return true;
    }
}
