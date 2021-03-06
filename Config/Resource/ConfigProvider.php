<?php

namespace Videni\Bundle\RapidGraphQLBundle\Config\Resource;

use Videni\Bundle\RapidGraphQLBundle\Exception\ConfigNotFoundException;

class ConfigProvider
{
    private $resourceConfigurations;
    private $resourceConfigsCache = [];

    private $operationConfigurations;
    private $operationConfigsCache = [];

    public function __construct(
        array $resourceConfigurations,
        array $operationConfigurations
    ) {
        $this->resourceConfigurations = $resourceConfigurations;
        $this->operationConfigurations = $operationConfigurations;
    }

    public function getResource($resourceName)
    {
        if (array_key_exists($resourceName, $this->resourceConfigsCache)) {
            return  $this->resourceConfigsCache[$resourceName];
        }

        if (isset($this->resourceConfigurations[$resourceName])) {
            $resourceConfig  = Resource::fromArray($this->resourceConfigurations[$resourceName]);

            $this->resourceConfigsCache[$resourceName] = $resourceConfig;

            return $resourceConfig;
        }

        throw new ConfigNotFoundException('Resource', $resourceName);
    }

    public function getResourceByClassName($class)
    {
        foreach($this->resourceConfigurations as $resourceName => $resourceConfig) {
            if ($resourceConfig['entity_class'] === $class) {
                $resource = Resource::fromArray($resourceConfig);

                $this->resourceConfigsCache[$resourceName] = $resource;

                return $resource;
            }
        }

        throw new ConfigNotFoundException('Resource', $class);
    }

    public function getAllResources()
    {
        foreach ($this->resourceConfigurations as $resourceName => $resourceConfiguration) {
            $this->resourceConfigsCache[$resourceName] =  Resource::fromArray($resourceConfiguration);
        }

        return $this->resourceConfigsCache;
    }

    public function getOperation($operationName)
    {
        if (array_key_exists($operationName, $this->operationConfigsCache)) {
            return  $this->operationConfigsCache[$operationName];
        }

        if (isset($this->operationConfigurations[$operationName])) {
            $operationConfig  = Operation::fromArray($this->operationConfigurations[$operationName]);

            $this->operationConfigsCache[$operationName] = $operationConfig;

            return $operationConfig;
        }

        throw new ConfigNotFoundException('Operation', $operationName);
    }

    public function getAllOperations()
    {
        foreach ($this->operationConfigurations as $operationName => $operationConfig) {
            $this->operationConfigsCache[$operationName] = Operation::fromArray($operationConfig);
        }

        return $this->operationConfigsCache;
    }
}
