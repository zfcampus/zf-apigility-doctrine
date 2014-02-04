<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\Filter\FilterChain;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver;
use ZF\Apigility\Admin\Exception;
use ZF\Configuration\ConfigResource;
use ZF\Configuration\ModuleUtils;
use ZF\Rest\Exception\CreationException;
use Zf\Apigility\Admin\Model\ModuleEntity;
use ZF\Apigility\Doctrine\Admin\Model\NewRestServiceEntity;
use ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceEntity as RestServiceEntity;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use ZF\ApiProblem\ApiProblem;

class DoctrineRestServiceModel implements EventManagerAwareInterface, ServiceManagerAwareInterface
{
    /**
     * @var ConfigResource
     */
    protected $configResource;

    /**
     * @var EventManagerInterface
     */
    protected $events;

    /**
     * @var string
     */
    protected $module;

    /**
     * @var ModuleEntity
     */
    protected $moduleEntity;

    /**
     * @var string
     */
    protected $modulePath;

    /**
     * @var ModuleUtils
     */
    protected $modules;

    /**
     * @var PhpRenderer
     */
    protected $renderer;

    /**
     * Allowed REST update options that are scalars
     *
     * @var array
     */
    protected $restScalarUpdateOptions = array(
        'pageSize'                 => 'page_size',
        'pageSizeParam'            => 'page_size_param',
        'entityClass'              => 'entity_class',
        'collectionClass'          => 'collection_class',
    );

    /**
     * Allowed REST update options that are arrays
     *
     * @var array
     */
    protected $restArrayUpdateOptions = array(
        'collectionHttpMethods'    => 'collection_http_methods',
        'collectionQueryWhitelist' => 'collection_query_whitelist',
        'resourceHttpMethods'      => 'resource_http_methods',
    );

    /**
     * @var FilterChain
     */
    protected $routeNameFilter;

    /**
     * @param  ModuleEntity $moduleEntity
     * @param  ModuleUtils $modules
     * @param  ConfigResource $config
     */
    public function __construct(ModuleEntity $moduleEntity, ModuleUtils $modules, ConfigResource $config)
    {
        $this->module         = $moduleEntity->getName();
        $this->moduleEntity   = $moduleEntity;
        $this->modules        = $modules;
        $this->configResource = $config;
        $this->modulePath     = $modules->getModulePath($this->module);
    }

    /**
     * Allow read-only access to properties
     *
     * @param  string $name
     * @return mixed
     * @throws \OutOfRangeException
     */
    public function __get($name)
    {
        if (!isset($this->{$name})) {
            throw new \OutOfRangeException(sprintf(
                'Cannot locate property by name of "%s"',
                $name
            ));
        }
        return $this->{$name};
    }

    protected $serviceManager;

    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        return $this;
    }

    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * Set the EventManager instance
     *
     * @param  EventManagerInterface $events
     * @return self
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers(array(
            __CLASS__,
            get_class($this),
        ));
        $this->events = $events;
        return $this;
    }

    /**
     * Retrieve the EventManager instance
     *
     * Lazy instantiates one if none currently registered
     *
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if (!$this->events) {
            $this->setEventManager(new EventManager());
        }
        return $this->events;
    }

    /**
     * @param  string $controllerService
     * @return RestServiceEntity|false
     */
    public function fetch($controllerService)
    {
        $config = $this->configResource->fetch(true);

        if (!isset($config['zf-rest'])
            || !isset($config['zf-rest'][$controllerService])
        ) {
            throw new Exception\RuntimeException(sprintf(
                'Could not find REST resource by name of %s',
                $controllerService
            ), 404);
        }

        $restConfig = $config['zf-rest'][$controllerService];

        $restConfig['controllerServiceName'] = $controllerService;
        $restConfig['module']                = $this->module;
        $restConfig['resource_class']        = $restConfig['listener'];
        unset($restConfig['listener']);

        $entity = new RestServiceEntity();
        $entity->exchangeArray($restConfig);

        $this->getRouteInfo($entity, $config);
        $this->mergeContentNegotiationConfig($controllerService, $entity, $config);
        $this->mergeHalConfig($controllerService, $entity, $config);

        // Trigger an event, allowing a listener to alter the entity and/or
        // curry a new one.
        $eventResults = $this->getEventManager()->trigger(__FUNCTION__, $this, array(
            'entity' => $entity,
            'config' => $config,
        ), function ($r) {
            return ($r instanceof RestServiceEntity);
        });
        if ($eventResults->stopped()) {
            return $eventResults->last();
        }

        return $entity;
    }

    /**
     * Fetch all services
     *
     * @return RestServiceEntity[]
     */
    public function fetchAll($version = null)
    {
        $config = $this->configResource->fetch(true);
        if (!isset($config['zf-rest'])) {
            return array();
        }

        $services = array();
        $pattern  = false;

        // Initialize pattern if a version was passed and it's valid
        if (null !== $version) {
            $version = (int) $version;
            if (!in_array($version, $this->moduleEntity->getVersions(), true)) {
                throw new Exception\RuntimeException(sprintf(
                    'Invalid version "%s" provided',
                    $version
                ), 400);
            }
            $namespaceSep = preg_quote('\\');
            $pattern = sprintf(
                '#%s%sV%s#',
                $this->module,
                $namespaceSep,
                $version
            );
        }

        foreach (array_keys($config['zf-rest']) as $controllerService) {
            if (!$pattern) {
                $services[] = $this->fetch($controllerService);
                continue;
            }

            if (preg_match($pattern, $controllerService)) {
                $services[] = $this->fetch($controllerService);
                continue;
            }
        }

        return $services;
    }

    /**
     * Create a new service using the details provided
     *
     * @param  NewRestServiceEntity $details
     * @return RestServiceEntity
     */
    public function createService(NewRestServiceEntity $details)
    {
        $resourceName = ucfirst($details->resourceName);

        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*(\\\[a-zA-Z][a-zA-Z0-9_]*)*$/', $resourceName)) {
            throw new CreationException('Invalid resource name; must be a valid PHP namespace name.');
        }

        $entity       = new RestServiceEntity();
        $entity->exchangeArray($details->getArrayCopy());

        $mediaType         = $this->createMediaType();
        $resourceClass     = ($details->resourceClass) ? $details->resourceClass: $this->createResourceClass($resourceName, $details);
        $collectionClass   = ($details->collectionClass) ? $details->collectionClass: $this->createCollectionClass($resourceName);
        $entityClass       = ($details->entityClass) ? $details->entityClass: $this->createEntityClass($resourceName, $details);
        $module            = ($details->module) ? $details->module: $this->module;

        $controllerService = ($details->controllerServiceName) ? $details->controllerServiceName: $this->createControllerServiceName($resourceName);
        $routeName         = ($details->routeName) ? $details->routeName: $this->createRoute($resourceName, $details->routeMatch, $details->routeIdentifierName, $controllerService);

        $objectManager     = ($details->objectManager) ? $details->objectManager: 'doctrine.entitymanager.orm_default';

        $entity->exchangeArray(array(
            'collection_class'        => $collectionClass,
            'controller_service_name' => $controllerService,
            'entity_class'            => $entityClass,
            'module'                  => $module,
            'resource_class'          => $resourceClass,
            'route_name'              => $routeName,
            'accept_whitelist'        => array(
                $mediaType,
                'application/hal+json',
                'application/json',
            ),
            'content_type_whitelist'  => array(
                $mediaType,
                'application/json',
            ),
            'object_manager' => $objectManager,
        ));

        $this->createRestConfig($entity, $controllerService, $resourceClass, $routeName);
        $this->createContentNegotiationConfig($entity, $controllerService);
        $this->createHalConfig($entity, $entityClass, $collectionClass, $routeName);
        $this->createDoctrineConfig($entity, $entityClass, $collectionClass, $routeName);

        return $entity;
    }

    /**
     * Update an existing service
     *
     * @param  RestServiceEntity $update
     * @return RestServiceEntity
     */
    public function updateService(RestServiceEntity $update)
    {
        $controllerService = $update->controllerServiceName;

        try {
            $original = $this->fetch($controllerService);
        } catch (Exception\RuntimeException $e) {
            throw new Exception\RuntimeException(sprintf(
                'Cannot update REST service "%s"; not found',
                $controllerService
            ), 404);
        }

        $this->updateRoute($original, $update);
        $this->updateRestConfig($original, $update);
        $this->updateContentNegotiationConfig($original, $update);

        return $this->fetch($controllerService);
    }

    /**
     * Delete a named service
     *
     * @todo   Remove content-negotiation and/or HAL configuration?
     * @param  string $controllerService
     * @return true
     */
    public function deleteService($controllerService)
    {
        try {
            $service = $this->fetch($controllerService);
        } catch (Exception\RuntimeException $e) {
            throw new Exception\RuntimeException(sprintf(
                'Cannot delete REST service "%s"; not found',
                $controllerService
            ), 404);
        }

        $this->deleteRoute($service);
        $response = $this->deleteDoctrineRestConfig($service);

        if ($response instanceof ApiProblem) {
            return $response;
        }

        return true;
    }

    /**
     * Generate the controller service name from the module and resource name
     *
     * @param  string $module
     * @param  string $resourceName
     * @return string
     */
    public function createControllerServiceName($resourceName)
    {
        return sprintf(
            '%s\\V%s\\Rest\\%s\\Controller',
            $this->module,
            $this->moduleEntity->getLatestVersion(),
            $resourceName
        );
    }

    /**
     * Creates a new resource class based on the specified resource name
     *
     * @param  string $resourceName
     * @return string The name of the newly created class
     */
    public function createResourceClass($resourceName, NewRestServiceEntity $details)
    {
        $module  = $this->module;
        $srcPath = $this->getSourcePath($resourceName);

        $className = sprintf('%sResource', $resourceName);
        $classPath = sprintf('%s/%s.php', $srcPath, $className);

        if (file_exists($classPath)) {
            throw new Exception\RuntimeException(sprintf(
                'The resource "%s" already exists',
                $className
            ));
        }

        $view = new ViewModel(array(
            'module'    => $module,
            'resource'  => $resourceName,
            'classname' => $className,
            'details'   => $details,
            'version'   => $this->moduleEntity->getLatestVersion(),
        ));
        if (!$this->createClassFile($view, 'resource', $classPath)) {
            throw new Exception\RuntimeException(sprintf(
                'Unable to create resource "%s"; unable to write file',
                $className
            ));
        }

        $fullClassName = sprintf(
            '%s\\V%s\\Rest\\%s\\%s',
            $module,
            $this->moduleEntity->getLatestVersion(),
            $resourceName,
            $className
        );

        return $fullClassName;
    }

    /**
     * Create an entity class for the resource
     *
     * @param  string $resourceName
     * @param  string $template Which template to use; defaults to 'entity'
     * @return string The name of the newly created entity class
     */
    public function createEntityClass($resourceName, $template = 'entity')
    {
        $module     = $this->module;
        $srcPath    = $this->getSourcePath($resourceName);

        $className = sprintf('%sEntity', $resourceName);
        $classPath = sprintf('%s/%s.php', $srcPath, $className);

        if (file_exists($classPath)) {
            throw new Exception\RuntimeException(sprintf(
                'The entity "%s" already exists',
                $className
            ));
        }

        $view = new ViewModel(array(
            'module'    => $module,
            'resource'  => $resourceName,
            'classname' => $className,
            'version'   => $this->moduleEntity->getLatestVersion(),
        ));
// Entity creation removed for Doctrine
        /*
        if (!$this->createClassFile($view, $template, $classPath)) {
            throw new Exception\RuntimeException(sprintf(
                'Unable to create entity "%s"; unable to write file',
                $className
            ));
        }
        */

        $fullClassName = sprintf(
            '%s\\V%s\\Rest\\%s\\%s',
            $module,
            $this->moduleEntity->getLatestVersion(),
            $resourceName,
            $className
        );
        return $fullClassName;
    }

    /**
     * Create a collection class for the resource
     *
     * @param  string $resourceName
     * @return string The name of the newly created collection class
     */
    public function createCollectionClass($resourceName)
    {
        $module     = $this->module;
        $srcPath    = $this->getSourcePath($resourceName);

        $className = sprintf('%sCollection', $resourceName);
        $classPath = sprintf('%s/%s.php', $srcPath, $className);

        if (file_exists($classPath)) {
            throw new Exception\RuntimeException(sprintf(
                'The collection "%s" already exists',
                $className
            ));
        }

        $view = new ViewModel(array(
            'module'    => $module,
            'resource'  => $resourceName,
            'classname' => $className,
            'version'   => $this->moduleEntity->getLatestVersion(),
        ));
        if (!$this->createClassFile($view, 'collection', $classPath)) {
            throw new Exception\RuntimeException(sprintf(
                'Unable to create entity "%s"; unable to write file',
                $className
            ));
        }

        $fullClassName = sprintf(
            '%s\\V%s\\Rest\\%s\\%s',
            $module,
            $this->moduleEntity->getLatestVersion(),
            $resourceName,
            $className
        );
        return $fullClassName;
    }

    /**
     * Create the route configuration
     *
     * @param  string $resourceName
     * @param  string $route
     * @param  string $identifier
     * @param  string $controllerService
     * @return string
     */
    public function createRoute($resourceName, $route, $identifier, $controllerService)
    {
        $filter    = $this->getRouteNameFilter();
        $routeName = sprintf(
            '%s.rest.%s',
            $filter->filter($this->module),
            $filter->filter($resourceName)
        );

        $config = array(
            'router' => array(
                'routes' => array(
                    $routeName => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => sprintf('%s[/:%s]', $route, $identifier),
                            'defaults' => array(
                                'controller' => $controllerService,
                            ),
                        ),
                    ),
                )
            ),
            'zf-versioning' => array(
                'uri' => array(
                    $routeName
                )
            )
        );
        $this->configResource->patch($config, true);

        return $routeName;
    }

    /**
     * Create the mediatype for this
     *
     * Based on the module and the latest module version.
     *
     * @return string
     */
    public function createMediaType()
    {
        $filter = $this->getRouteNameFilter();
        return sprintf(
            'application/vnd.%s.v%s+json',
            $filter->filter($this->module),
            $this->moduleEntity->getLatestVersion()
        );
    }

    /**
     * Creates REST configuration
     *
     * @param  RestServiceEntity $details
     * @param  string $controllerService
     * @param  string $resourceClass
     * @param  string $routeName
     */
    public function createRestConfig(DoctrineRestServiceEntity $details, $controllerService, $resourceClass, $routeName)
    {
        $config = array('zf-rest' => array(
            $controllerService => array(
                'listener'                   => $resourceClass,
                'route_name'                 => $routeName,
                'route_identifier_name'      => $details->routeIdentifierName,
                'entity_identifier_name'     => $details->entityIdentifierName,
                'collection_name'            => $details->collectionName,
                'resource_http_methods'      => $details->resourceHttpMethods,
                'collection_http_methods'    => $details->collectionHttpMethods,
                'collection_query_whitelist' => $details->collectionQueryWhitelist,
                'page_size'                  => $details->pageSize,
                'page_size_param'            => $details->pageSizeParam,
                'entity_class'               => $details->entityClass,
                'collection_class'           => $details->collectionClass,
            ),
        ));
        $this->configResource->patch($config, true);
    }

    /**
     * Create content negotiation configuration based on payload and discovered
     * controller service name
     *
     * @param  RestServiceEntity $details
     * @param  string $controllerService
     */
    public function createContentNegotiationConfig(RestServiceEntity $details, $controllerService)
    {
        $config = array(
            'controllers' => array(
                $controllerService => $details->selector,
            ),
        );
        $whitelist = $details->acceptWhitelist;
        if (!empty($whitelist)) {
            $config['accept-whitelist'] = array($controllerService => $whitelist);
        }
        $whitelist = $details->contentTypeWhitelist;
        if (!empty($whitelist)) {
            $config['content-type-whitelist'] = array($controllerService => $whitelist);
        }
        $config = array('zf-content-negotiation' => $config);
        $this->configResource->patch($config, true);
    }

    /**
     * Create Doctrine configuration
     *
     * @param  RestServiceEntity $details
     * @param  string $entityClass
     * @param  string $collectionClass
     * @param  string $routeName
     */
    public function createDoctrineConfig(RestServiceEntity $details, $entityClass, $collectionClass, $routeName)
    {
        $entityValue = $details->getArrayCopy();
        $objectManager = $this->getServiceManager()->get($details->objectManager);
        $hydratorStrategies = array();

        // Add all ORM collections to Hydrator Strategies
        if ($objectManager instanceof \Doctrine\ORM\EntityManager) {
            $collectionStrategyName = 'ZF\Apigility\Doctrine\Server\Hydrator\Strategy\CollectionLink';
            $metadataFactory = $objectManager->getMetadataFactory();
            $metadata = $metadataFactory->getMetadataFor($entityClass);

            foreach ($metadata->associationMappings as $relationName => $relationMapping) {
                switch ($relationMapping['type']) {
                    case 4:
                        $hydratorStrategies[$relationName] = $collectionStrategyName;
                        break;
                    default:
                        break;
                }
            }
        }

        // The abstract_factories key is set to the value so these factories do not get duplicaed with each resource
        $config = array(
            'service_manager' => array(
                'abstract_factories' => array(
                    'ZF\Apigility\Doctrine\Server\Resource\DoctrineResourceFactory' => 'ZF\Apigility\Doctrine\Server\Resource\DoctrineResourceFactory',
                ),
                'invokables' => array(
                    'ZF\Apigility\\Doctrine\\Server\\Hydrator\\Strategy\\CollectionLink' => 'ZF\Apigility\\Doctrine\\Server\\Hydrator\\Strategy\\CollectionLink',
                ),
            ),
            'zf-rest-doctrine-hydrator' => array(
                $details->hydratorName => array(
                    'entity_class' => $entityClass,
                    'object_manager' => $details->objectManager,
                    'by_value' => $entityValue['hydrate_by_value'],
                    'strategies' => $hydratorStrategies,
                ),
            ),
            'zf-apigility' => array(
                'doctrine-connected' => array(
                    $details->resourceClass => array(
                        'object_manager' => $details->objectManager,
                        'hydrator' => $details->hydratorName,
                    ),
                ),
            ),
        );

        $this->configResource->patch($config, true);
    }

    /**
     * Create HAL configuration
     *
     * @param  RestServiceEntity $details
     * @param  string $entityClass
     * @param  string $collectionClass
     * @param  string $routeName
     */
    public function createHalConfig(RestServiceEntity $details, $entityClass, $collectionClass, $routeName)
    {
        $config = array('zf-hal' => array('metadata_map' => array(
            $entityClass => array(
                'route_identifier_name' => $details->routeIdentifierName,
                'entity_identifier_name' => $details->entityIdentifierName,
                'route_name'      => $routeName,
            ),
            $collectionClass => array(
                'entity_identifier_name' => $details->entityIdentifierName,
                'route_name'      => $routeName,
                'is_collection'   => true,
            ),
        )));
        if (isset($details->hydratorName)) {
            $config['zf-hal']['metadata_map'][$entityClass]['hydrator'] = $details->hydratorName;
        }
        $this->configResource->patch($config, true);
    }

    /**
     * Update the route for an existing service
     *
     * @param  RestServiceEntity $original
     * @param  RestServiceEntity $update
     */
    public function updateRoute(RestServiceEntity $original, RestServiceEntity $update)
    {
        $route = $update->routeMatch;
        if (!$route) {
            return;
        }
        $routeName = $original->routeName;
        $config    = array('router' => array('routes' => array(
            $routeName => array('options' => array(
                'route' => $route,
            ))
        )));
        $this->configResource->patch($config, true);
    }

    /**
     * Update REST configuration
     *
     * @param  RestServiceEntity $original
     * @param  RestServiceEntity $update
     */
    public function updateRestConfig(RestServiceEntity $original, RestServiceEntity $update)
    {
        $patch = array();
        foreach ($this->restScalarUpdateOptions as $property => $configKey) {
            if (!$update->$property) {
                continue;
            }
            $patch[$configKey] = $update->$property;
        }

        if (empty($patch)) {
            goto updateArrayOptions;
        }

        $config = array('zf-rest' => array(
            $original->controllerServiceName => $patch,
        ));
        $this->configResource->patch($config, true);

        updateArrayOptions:

        foreach ($this->restArrayUpdateOptions as $property => $configKey) {
            if (!$update->$property) {
                continue;
            }
            $key = sprintf('zf-rest.%s.%s', $original->controllerServiceName, $configKey);
            $this->configResource->patchKey($key, $update->$property);
        }
    }

    /**
     * Update the content negotiation configuration for the service
     *
     * @param  RestServiceEntity $original
     * @param  RestServiceEntity $update
     */
    public function updateContentNegotiationConfig(RestServiceEntity $original, RestServiceEntity $update)
    {
        $baseKey = 'zf-content-negotiation.';
        $service = $original->controllerServiceName;

        if ($update->selector) {
            $key = $baseKey . 'controllers.' . $service;
            $this->configResource->patchKey($key, $update->selector);
        }

        // Array dereferencing is a PITA
        $acceptWhitelist = $update->acceptWhitelist;
        if (is_array($acceptWhitelist)
            && !empty($acceptWhitelist)
        ) {
            $key = $baseKey . 'accept-whitelist.' . $service;
            $this->configResource->patchKey($key, $acceptWhitelist);
        }

        $contentTypeWhitelist = $update->contentTypeWhitelist;
        if (is_array($contentTypeWhitelist)
            && !empty($contentTypeWhitelist)
        ) {
            $key = $baseKey . 'content-type-whitelist.' . $service;
            $this->configResource->patchKey($key, $contentTypeWhitelist);
        }
    }

    /**
     * Delete the route associated with the given service
     *
     * @param  RestServiceEntity $entity
     */
    public function deleteRoute(RestServiceEntity $entity)
    {
        $config = $this->configResource->fetch(true);

        $route = $entity->routeName;
        $key   = array('router', 'routes', $route);
        $this->configResource->deleteKey($key);

        $uriKey = array_search($route, $config['zf-versioning']['uri']);
        if ($uriKey !== false) {
            $key = array('zf-versioning', 'uri', $uriKey);
            $this->configResource->deleteKey($key);
        }
    }

    /**
     * Delete the REST configuration associated with the given
     * service
     *
     * @param  RestServiceEntity $entity
     */
    public function deleteDoctrineRestConfig(RestServiceEntity $entity)
    {
         // Get hydrator name
         $config = $this->configResource->fetch(true);
         $hydratorName = $config['zf-hal']['metadata_map'][$entity->entityClass]['hydrator'];
         $objectManagerClass = $config['zf-rest-doctrine-hydrator'][$hydratorName]['object_manager'];

         $key = array('zf-rest-doctrine-hydrator', $hydratorName);
         $this->configResource->deleteKey($key);

         $key = array('zf-apigility', 'doctrine-connected', $entity->resourceClass);
         $this->configResource->deleteKey($key);

        $key = array('zf-rest', $entity->controllerServiceName);
        $this->configResource->deleteKey($key);

        $key = array('zf-content-negotiation', 'controllers', $entity->controllerServiceName);
        $this->configResource->deleteKey($key);

        $key = array('zf-content-negotiation', 'accept-whitelist', $entity->controllerServiceName);
        $this->configResource->deleteKey($key);

        $key = array('zf-content-negotiation', 'content-type-whitelist', $entity->controllerServiceName);
        $this->configResource->deleteKey($key);

        $key = array('zf-hal', 'metadata_map', $entity->collectionClass);
        $this->configResource->deleteKey($key);

        $key = array('zf-hal', 'metadata_map', $entity->entityClass);
        $this->configResource->deleteKey($key);

        $objectManager = $this->getServiceManager()->get($objectManagerClass);
        if ($objectManager instanceof \Doctrine\ORM\EntityManager) {

            $metadataFactory = $objectManager->getMetadataFactory();
            $metadata = $metadataFactory->getMetadataFor($entity->entityClass);

            foreach ($metadata->associationMappings as $relationName => $relationMapping) {
                switch ($relationMapping['type']) {
                    case 4:

                        $resourceName = substr($entity->resourceClass,
                            strlen($this->module . '\\' . $this->moduleEntity->getLatestVersion() . '\\Rest\\') + 1);
                        $resourceName = substr($resourceName, 0, strlen($resourceName) - 15);

                        $rpcServiceName = $this->module . '\\V' . $this->moduleEntity->getLatestVersion() . '\\Rpc\\'
                            . $resourceName . $relationName . '\\Controller';

                        $doctrineRpcServiceResource = $this->getServiceManager()->get('ZF\Apigility\Doctrine\Admin\Model\DoctrineRpcServiceResource');
                        $doctrineRpcServiceResource->setModuleName($this->module);

                        $response = $doctrineRpcServiceResource->delete($rpcServiceName);
                        if ($response instanceof ApiProblem) {
                            return $response;
                        }

                        break;
                    default:
                        break;
                }
            }
        }
    }

    /**
     * Create a class file
     *
     * Creates a class file based on the view model passed, the type of resource,
     * and writes it to the path provided.
     *
     * @param  ViewModel $model
     * @param  string $type
     * @param  string $classPath
     * @return bool
     */
    protected function createClassFile(ViewModel $model, $type, $classPath)
    {
        $renderer = $this->getRenderer();
        $template = $this->injectResolver($renderer, $type);
        $model->setTemplate($template);

        if (file_put_contents(
            $classPath,
            '<' . "?php\n" . $renderer->render($model)
        )) {
            return true;
        }

        return false;
    }

    /**
     * Get a renderer instance
     *
     * @return PhpRenderer
     */
    protected function getRenderer()
    {
        if ($this->renderer instanceof PhpRenderer) {
            return $this->renderer;
        }

        $this->renderer = new PhpRenderer();
        return $this->renderer;
    }

    /**
     * Inject the renderer with a resolver
     *
     * Seed the resolver with a template name and path based on the $type passed, and inject it
     * into the renderer.
     *
     * @param  PhpRenderer $renderer
     * @param  string $type
     * @return string Template name
     */
    protected function injectResolver(PhpRenderer $renderer, $type)
    {
        $template = sprintf('doctrine/rest-', $type);
        $path     = sprintf('%s/../../../../../../view/doctrine/rest-%s.phtml', __DIR__, $type);
        $resolver = new Resolver\TemplateMapResolver(array(
            $template => $path,
        ));
        $renderer->setResolver($resolver);
        return $template;
    }

    /**
     * Get the source path for the module
     *
     * @param  string $resourceName
     * @return string
     */
    protected function getSourcePath($resourceName)
    {
        $sourcePath = sprintf(
            '%s/src/%s/V%s/Rest/%s',
            $this->modulePath,
            str_replace('\\', '/', $this->module),
            $this->moduleEntity->getLatestVersion(),
            $resourceName
        );

        if (!file_exists($sourcePath)) {
            mkdir($sourcePath, 0777, true);
        }

        return $sourcePath;
    }

    /**
     * Retrieve the filter chain for generating the route name
     *
     * @return FilterChain
     */
    protected function getRouteNameFilter()
    {
        if ($this->routeNameFilter instanceof FilterChain) {
            return $this->routeNameFilter;
        }

        $this->routeNameFilter = new FilterChain();
        $this->routeNameFilter->attachByName('Word\CamelCaseToDash')
            ->attachByName('StringToLower');
        return $this->routeNameFilter;
    }

    /**
     * Retrieve route information for a given service based on the configuration available
     *
     * @param  RestServiceEntity $metadata
     * @param  array $config
     */
    protected function getRouteInfo(RestServiceEntity $metadata, array $config)
    {
        $routeName = $metadata->routeName;
        if (!$routeName
            || !isset($config['router'])
            || !isset($config['router']['routes'])
            || !isset($config['router']['routes'][$routeName])
            || !isset($config['router']['routes'][$routeName]['options'])
            || !isset($config['router']['routes'][$routeName]['options']['route'])
        ) {
            return;
        }
        $metadata->exchangeArray(array(
            'route_match' => $config['router']['routes'][$routeName]['options']['route'],
        ));
    }

    /**
     * Merge the content negotiation configuration for the given controller
     * service into the REST metadata
     *
     * @param  string $controllerServiceName
     * @param  RestServiceEntity $metadata
     * @param  array $config
     */
    protected function mergeContentNegotiationConfig($controllerServiceName, RestServiceEntity $metadata, array $config)
    {
        if (!isset($config['zf-content-negotiation'])) {
            return;
        }
        $config = $config['zf-content-negotiation'];

        if (isset($config['controllers'])
            && isset($config['controllers'][$controllerServiceName])
        ) {
            $metadata->exchangeArray(array(
                'selector' => $config['controllers'][$controllerServiceName],
            ));
        }

        if (isset($config['accept-whitelist'])
            && isset($config['accept-whitelist'][$controllerServiceName])
        ) {
            $metadata->exchangeArray(array(
                'accept_whitelist' => $config['accept-whitelist'][$controllerServiceName],
            ));
        }

        if (isset($config['content-type-whitelist'])
            && isset($config['content-type-whitelist'][$controllerServiceName])
        ) {
            $metadata->exchangeArray(array(
                'content-type-whitelist' => $config['content-type-whitelist'][$controllerServiceName],
            ));
        }
    }

    /**
     * Merge entity and collection class into metadata, if found
     *
     * @param  string $controllerServiceName
     * @param  RestServiceEntity $metadata
     * @param  array $config
     */
    protected function mergeHalConfig($controllerServiceName, RestServiceEntity $metadata, array $config)
    {
        if (!isset($config['zf-hal'])
            || !isset($config['zf-hal']['metadata_map'])
        ) {
            return;
        }

        $config = $config['zf-hal']['metadata_map'];

        $entityClass     = $this->deriveEntityClass($controllerServiceName, $metadata, $config);
        $collectionClass = $this->deriveCollectionClass($controllerServiceName, $metadata, $config);
        $merge           = array();

        if (isset($config[$entityClass])) {
            $merge['entity_class'] = $entityClass;
        }

        if (isset($config[$collectionClass])) {
            $merge['collection_class'] = $collectionClass;
        }

        $metadata->exchangeArray($merge);
    }

    /**
     * Derive the name of the entity class from the controller service name
     *
     * @param  string $controllerServiceName
     * @param  RestServiceEntity $metadata
     * @param  array $config
     * @return string
     */
    protected function deriveEntityClass($controllerServiceName, RestServiceEntity $metadata, array $config)
    {
        if (isset($config['zf-rest'])
            && isset($config['zf-rest'][$controllerServiceName])
            && isset($config['zf-rest'][$controllerServiceName]['entity_class'])
        ) {
            return $config['zf-rest'][$controllerServiceName]['entity_class'];
        }

        $module = ($metadata->module == $this->module) ? $this->module : $metadata->module;
        if (!preg_match('#' . preg_quote($module . '\\Rest\\') . '(?P<service>[^\\\\]+)' . preg_quote('\\Controller') . '#', $controllerServiceName, $matches)) {
            return null;
        }
        return sprintf('%s\\Rest\\%s\\%sEntity', $module, $matches['service'], $matches['service']);
    }

    /**
     * Derive the name of the collection class from the controller service name
     *
     * @param  string $controllerServiceName
     * @param  RestServiceEntity $metadata
     * @param  array $config
     * @return string
     */
    protected function deriveCollectionClass($controllerServiceName, RestServiceEntity $metadata, array $config)
    {
        if (isset($config['zf-rest'])
            && isset($config['zf-rest'][$controllerServiceName])
            && isset($config['zf-rest'][$controllerServiceName]['collection_class'])
        ) {
            return $config['zf-rest'][$controllerServiceName]['collection_class'];
        }

        $module = ($metadata->module == $this->module) ? $this->module : $metadata->module;
        if (!preg_match('#' . preg_quote($module . '\\Rest\\') . '(?P<service>[^\\\\]+)' . preg_quote('\\Controller') . '#', $controllerServiceName, $matches)) {
            return null;
        }
        return sprintf('%s\\Rest\\%s\\%sCollection', $module, $matches['service'], $matches['service']);
    }
}
