<?php

namespace Videni\Bundle\RestBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Alias;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepositoryInterface;
use Doctrine\Common\Inflector\Inflector;
use Videni\Bundle\RestBundle\Config\Resource\ResourceProvider;
use Videni\Bundle\RestBundle\Config\Resource\Resource;
use Videni\Bundle\RestBundle\Config\Resource\Service;
use Videni\Bundle\RestBundle\Factory\Factory;
use Videni\Bundle\RestBundle\Doctrine\ORM\ServiceEntityRepository;
use Videni\Bundle\RestBundle\Doctrine\ORM\EntityRepository;
use Videni\Bundle\RestBundle\Util\DependencyInjectionUtil;

class RegisterResourcesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $bundleConifig = DependencyInjectionUtil::getConfig($container);

        $resourceConfigProvider = $container->get(ResourceProvider::class);

        $resourceConfigs = $resourceConfigProvider->getAll();
        foreach ($resourceConfigs as $className => $resourceConfig) {
            //register entity class parameter
            $container->setParameter(sprintf('%s.class', $this->getServiceId($resourceConfig->getScope(), $resourceConfig->getShortName(), 'entity')), $className);

            $this->registerFactory($className, $resourceConfig, $container);
            $this->registerRepository($className, $resourceConfig, $container);
        }
    }

    private function registerFactory($className, Resource $resourceConfig, $container)
    {
        $factoryClass = $resourceConfig->getFactoryClass();

        $aliasId =  self::getServiceId($resourceConfig->getScope(), $resourceConfig->getShortName(), 'factory');

        $alias = new Alias($factoryClass);
        $alias->setPublic(true);
        if ($container->has($factoryClass)) {
            $container->setAlias($aliasId, $alias);
             //don't register if a factory is associated with this resource
            return;
        }

        $container->setParameter(sprintf('%s.class', $aliasId), $factoryClass);

        $factoryDef = (new Definition($factoryClass))
            ->addArgument($className)
            ->setPublic(true)
        ;

        //register it with class name as service name and also add an alias
        if ($factoryClass !== Factory::class) {
            $container->setDefinition($factoryClass, $factoryDef);
            $container->setAlias($aliasId, $alias);
        } else {
            $container->setDefinition($aliasId, $factoryDef);
        }
    }

    private function registerRepository($className, Resource $resourceConfig, $container)
    {
        $repositoryClass = $resourceConfig->getRepositoryClass();

        $aliasId = self::getServiceId($resourceConfig->getScope(), $resourceConfig->getShortName(), 'repository');
        $container->setParameter(sprintf('%s.class', $aliasId), $repositoryClass);

        $alias = new Alias($repositoryClass);
        $alias->setPublic(true);

        if ($container->has($repositoryClass)) {
            $container->setAlias($aliasId, $alias);

            return;
        }

        if (is_a($repositoryClass, ServiceEntityRepositoryInterface::class, true) && !$container->has($repositoryClass)) {
            throw new \RuntimeException(sprintf('The repository %s is an instance of %s, please register it into service container yourself', $repositoryClass, ServiceEntityRepositoryInterface::class));
        }

        $definition = new Definition($repositoryClass);
        $definition
            ->setArguments([
                new Reference($this->getManagerServiceId($resourceConfig)),
                $this->getClassMetadataDefinition($className, $resourceConfig),
            ])
            ->setPublic(true)
        ;

        if (!in_array($repositoryClass, [ServiceEntityRepository::class, EntityRepository::class])) {
            $container->setDefinition($repositoryClass, $definition);
            $container->setAlias($aliasId, $alias);
        } else {
            $container->setDefinition($aliasId, $definition);
        }
    }

    private function getServiceId($scope, $resourceShortName, $key)
    {
         $name = Inflector::tableize($resourceShortName);

         return sprintf('%s.%s.%s', $scope, $key, $name);
    }

    protected function getClassMetadataDefinition($className, Resource $resourceConfig): Definition
    {
        $definition = new Definition($this->getClassMetadataClassname());
        $definition
            ->setFactory([new Reference($this->getManagerServiceId($resourceConfig)), 'getClassMetadata'])
            ->setArguments([$className])
            ->setPublic(false)
        ;

        return $definition;
    }

    /**
     * {@inheritdoc}
     */
    protected function getClassMetadataClassname(): string
    {
        return 'Doctrine\\ORM\\Mapping\\ClassMetadata';
    }

     /**
     * {@inheritdoc}
     */
    protected function getManagerServiceId(Resource $resourceConfig): string
    {
        return 'doctrine.orm.entity_manager';
    }
}
