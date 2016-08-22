<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use OutOfRangeException;
use ReflectionClass;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\Filter\FilterChain;
use Zend\Filter\StringToLower;
use Zend\Filter\Word\CamelCaseToDash;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver;
use ZF\Apigility\Admin\Exception;
use ZF\Apigility\Admin\Model\ModuleEntity;
use ZF\Apigility\Admin\Model\ModulePathSpec;
use ZF\Apigility\Admin\Utility;
use ZF\ApiProblem\ApiProblem;
use ZF\Configuration\ConfigResource;
use ZF\Rest\Exception\CreationException;

class DoctrineRestServiceModel implements EventManagerAwareInterface
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
     * @var ModulePathSpec
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
    protected $restScalarUpdateOptions = [
        'pageSize'             => 'page_size',
        'pageSizeParam'        => 'page_size_param',
        'entityClass'          => 'entity_class',
        'entityIdentifierName' => 'entity_identifier_name',
        'collectionClass'      => 'collection_class',
        'collectionName'       => 'collection_name',
    ];

    /**
     * Allowed REST update options that are arrays
     *
     * @var array
     */
    protected $restArrayUpdateOptions = [
        'collectionHttpMethods'    => 'collection_http_methods',
        'collectionQueryWhitelist' => 'collection_query_whitelist',
        'entityHttpMethods'        => 'entity_http_methods',
    ];

    /**
     * @var array
     */
    protected $doctrineHydratorOptions = [
        'entityClass'          => 'entity_class',
        'objectManager'        => 'object_manager',
        'byValue'              => 'by_value',
        'useGeneratedHydrator' => 'use_generated_hydrator',
        'hydratorStrategies'   => 'strategies',
    ];

    /**
     * @var FilterChain
     */
    protected $routeNameFilter;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @param ModuleEntity $moduleEntity
     * @param ModulePathSpec $modules
     * @param ConfigResource $config
     */
    public function __construct(ModuleEntity $moduleEntity, ModulePathSpec $modules, ConfigResource $config)
    {
        $this->module         = $moduleEntity->getName();
        $this->moduleEntity   = $moduleEntity;
        $this->modules        = $modules;
        $this->configResource = $config;
        $this->modulePath     = $modules->getModulePath($this->module);
    }

    /**
     * Determine if the given entity is doctrine-connected, and, if so, recast to a DoctrineRestServiceEntity
     *
     * @param \Zend\EventManager\EventInterface $event
     * @return null|DoctrineRestServiceEntity
     */
    public static function onFetch($event)
    {
        $entity = $event->getParam('entity', false);
        if (! $entity) {
            // No entity; nothing to do
            return;
        }

        $config = $event->getParam('config', []);
        if (! isset($config['zf-apigility']['doctrine-connected'][$entity->resourceClass])) {
            // No DB-connected configuration for this service; nothing to do
            return;
        }

        // TODO : Move hydrators handling into separate model ?
        $configResource = $config['zf-apigility']['doctrine-connected'][$entity->resourceClass];

        if (isset($config['doctrine-hydrator']) && isset($config['doctrine-hydrator'][$configResource['hydrator']])) {
            $configHydrator = $config['doctrine-hydrator'][$configResource['hydrator']];
            $config = array_merge($configResource, $configHydrator);
        } else {
            $config = $configResource;
        }

        $doctrineEntity = new DoctrineRestServiceEntity();
        $doctrineEntity->exchangeArray(array_merge($entity->getArrayCopy(), $config));

        return $doctrineEntity;
    }

    /**
     * Allow read-only access to properties
     *
     * @param string $name
     * @return mixed
     * @throws OutOfRangeException
     */
    public function __get($name)
    {
        if (! isset($this->{$name})) {
            throw new OutOfRangeException(sprintf(
                'Cannot locate property by name of "%s"',
                $name
            ));
        }

        return $this->{$name};
    }

    /**
     * Get service manager
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * Set service manager
     *
     * @param ServiceManager $serviceManager
     * @return DoctrineRestServiceModel
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;
        return $this;
    }

    /**
     * Set the EventManager instance
     *
     * @param EventManagerInterface $events
     * @return self
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $events->setIdentifiers([
            __CLASS__,
            get_class($this),
        ]);
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
        if (! $this->events) {
            $this->setEventManager(new EventManager());
        }

        return $this->events;
    }

    /**
     * @param string $controllerService
     * @return DoctrineRestServiceEntity|false
     */
    public function fetch($controllerService)
    {
        $config = $this->configResource->fetch(true);

        if (! isset($config['zf-rest'][$controllerService])) {
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

        $entity = new DoctrineRestServiceEntity();
        $entity->exchangeArray($restConfig);

        $this->getRouteInfo($entity, $config);
        $this->mergeContentNegotiationConfig($controllerService, $entity, $config);
        $this->mergeHalConfig($controllerService, $entity, $config);

        if (empty($entity->serviceName)) {
            $serviceName = $controllerService;
            $q = preg_quote('\\');
            if (preg_match(
                vsprintf(
                    '#%sV[^%s]+%sRest%s(?<service>[^%s]+)%sController#',
                    array_fill(0, 6, $q)
                ),
                $controllerService,
                $matches
            )) {
                $serviceName = $matches['service'];
            }

            $entity->exchangeArray([
                'service_name' => $serviceName,
            ]);
        }

        // Trigger an event, allowing a listener to alter the entity and/or
        // curry a new one.
        $eventResults = $this->getEventManager()->trigger(
            __FUNCTION__,
            $this,
            [
                'entity' => $entity,
                'config' => $config,
            ],
            function ($r) {
                return ($r instanceof DoctrineRestServiceEntity);
            }
        );
        if ($eventResults->stopped()) {
            return $eventResults->last();
        }

        return $entity;
    }

    /**
     * Fetch all Doctrine services
     *
     * @param string $version
     * @return array
     */
    public function fetchAll($version = null)
    {
        $config = $this->configResource->fetch(true);
        if (! isset($config['zf-rest'])) {
            return [];
        }

        $services = [];
        $pattern  = false;

        // Initialize pattern if a version was passed and it is valid
        if (null !== $version) {
            $version = (int) $version;
            if (! in_array($version, $this->moduleEntity->getVersions(), true)) {
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
            // Because a version is always supplied this check may not be necessary
            if (! $pattern) {
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
     * Create a default hydrator name
     *
     * @param string $resourceName
     * @return string
     */
    public function createHydratorName($resourceName)
    {
        return sprintf(
            '%s\\V%s\\Rest\\%s\\%sHydrator',
            $this->module,
            $this->moduleEntity->getLatestVersion(),
            $resourceName,
            $resourceName
        );
    }

    /**
     * Create a new service using the details provided
     *
     * @param NewDoctrineServiceEntity $details
     * @return DoctrineRestServiceEntity
     * @throws CreationException
     */
    public function createService(NewDoctrineServiceEntity $details)
    {
        $resourceName = ucfirst($details->serviceName);

        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_]*(\\\[a-zA-Z][a-zA-Z0-9_]*)*$/', $resourceName)) {
            throw new CreationException('Invalid resource name; must be a valid PHP namespace name.');
        }

        if (! $this->getServiceManager()->has($details->objectManager)) {
            throw new CreationException(
                'Invalid object manager specified. Must be declared in the service manager.',
                422
            );
        }

        $entity = new DoctrineRestServiceEntity();
        $entity->exchangeArray($details->getArrayCopy());

        $mediaType = $this->createMediaType();

        $resourceClass = $details->resourceClass ?: $this->createResourceClass($resourceName, $details);
        $collectionClass = $details->collectionClass ?: $this->createCollectionClass($resourceName);
        $serviceName = $details->serviceName ?: $resourceName;

        $entityClass = $details->entityClass;
        if (! $entityClass || ! class_exists($entityClass)) {
            throw new CreationException('entityClass is required and must exist');
        }
        $module = $details->module ?: $this->module;

        $controllerService = $details->controllerServiceName ?: $this->createControllerServiceName($resourceName);

        $routeName = $details->routeName ?: $this->createRoute(
            $resourceName,
            $details->routeMatch,
            $details->routeIdentifierName,
            $controllerService
        );
        $hydratorName  = $details->hydratorName ?: $this->createHydratorName($resourceName);
        $objectManager = $details->objectManager ?: 'doctrine.entitymanager.orm_default';

        $entity->exchangeArray([
            'service_name'            => $serviceName,
            'collection_class'        => $collectionClass,
            'controller_service_name' => $controllerService,
            'entity_class'            => $entityClass,
            'hydrator_name'           => $hydratorName,
            'module'                  => $module,
            'resource_class'          => $resourceClass,
            'route_name'              => $routeName,
            'accept_whitelist'        => [
                $mediaType,
                'application/hal+json',
                'application/json',
            ],
            'content_type_whitelist'  => [
                $mediaType,
                'application/json',
            ],
            'object_manager' => $objectManager,
        ]);

        $this->createRestConfig($entity, $controllerService, $resourceClass, $routeName);
        $this->createContentNegotiationConfig($entity, $controllerService);
        $this->createHalConfig($entity, $entityClass, $collectionClass, $routeName);
        $this->createDoctrineConfig($entity, $entityClass, $collectionClass, $routeName);
        $this->createDoctrineHydratorConfig($entity, $entityClass, $collectionClass, $routeName);

        $this->getEventManager()->trigger(
            __FUNCTION__,
            $this,
            [
                'entity' => $entity,
                'configResource' => $this->configResource,
            ]
        );

        return $entity;
    }

    /**
     * Update an existing service
     *
     * @param DoctrineRestServiceEntity $update
     * @return DoctrineRestServiceEntity
     */
    public function updateService(DoctrineRestServiceEntity $update)
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
        $this->updateDoctrineConfig($original, $update);
        $this->updateDoctrineHydratorConfig($original, $update);
        $this->updateContentNegotiationConfig($original, $update);

        return $this->fetch($controllerService);
    }

    /**
     * Delete a named service
     *
     * @todo Remove content-negotiation and/or HAL configuration?
     * @param string $controllerService
     * @param bool $recursive
     * @return true
     */
    public function deleteService($controllerService, $recursive = false)
    {
        try {
            $service = $this->fetch($controllerService);
        } catch (Exception\RuntimeException $e) {
            throw new Exception\RuntimeException(sprintf(
                'Cannot delete REST service "%s"; not found',
                $controllerService
            ), 404);
        }

        if ($recursive) {
            $reflection = new ReflectionClass($service->resourceClass);
            Utility::recursiveDelete(dirname($reflection->getFileName()));
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
     * @param string $resourceName
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
     * @param string $resourceName
     * @return string The name of the newly created class
     */
    public function createResourceClass($resourceName, NewDoctrineServiceEntity $details)
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

        $view = new ViewModel([
            'module'    => $module,
            'resource'  => $resourceName,
            'classname' => $className,
            'details'   => $details,
            'version'   => $this->moduleEntity->getLatestVersion(),
        ]);

        if (! $this->createClassFile($view, 'resource', $classPath)) {
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
     * Create a collection class for the resource
     *
     * @param string $resourceName
     * @return string The name of the newly created collection class
     */
    public function createCollectionClass($resourceName)
    {
        $module    = $this->module;
        $srcPath   = $this->getSourcePath($resourceName);

        $className = sprintf('%sCollection', $resourceName);
        $classPath = sprintf('%s/%s.php', $srcPath, $className);

        if (file_exists($classPath)) {
            throw new Exception\RuntimeException(sprintf(
                'The collection "%s" already exists',
                $className
            ));
        }

        $view = new ViewModel([
            'module'    => $module,
            'resource'  => $resourceName,
            'classname' => $className,
            'version'   => $this->moduleEntity->getLatestVersion(),
        ]);

        if (! $this->createClassFile($view, 'collection', $classPath)) {
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
     * @param string $resourceName
     * @param string $route
     * @param string $identifier
     * @param string $controllerService
     * @return string
     */
    public function createRoute($resourceName, $route, $identifier, $controllerService)
    {
        $filter    = $this->getRouteNameFilter();
        $routeName = sprintf(
            '%s.rest.doctrine.%s',
            $filter->filter($this->module),
            $filter->filter($resourceName)
        );

        $config = [
            'router' => [
                'routes' => [
                    $routeName => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => sprintf('%s[/:%s]', $route, $identifier),
                            'defaults' => [
                                'controller' => $controllerService,
                            ],
                        ],
                    ],
                ],
            ],
            'zf-versioning' => [
                'uri' => [
                    $routeName,
                ],
            ],
        ];
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
     * @param DoctrineRestServiceEntity $details
     * @param string $controllerService
     * @param string $resourceClass
     * @param string $routeName
     */
    public function createRestConfig(DoctrineRestServiceEntity $details, $controllerService, $resourceClass, $routeName)
    {
        $config = ['zf-rest' => [
            $controllerService => [
                'listener'                   => $resourceClass,
                'route_name'                 => $routeName,
                'route_identifier_name'      => $details->routeIdentifierName,
                'entity_identifier_name'     => $details->entityIdentifierName,
                'collection_name'            => $details->collectionName,
                'entity_http_methods'        => $details->entityHttpMethods,
                'collection_http_methods'    => $details->collectionHttpMethods,
                'collection_query_whitelist' => $details->collectionQueryWhitelist ?: [],
                'page_size'                  => $details->pageSize,
                'page_size_param'            => $details->pageSizeParam,
                'entity_class'               => $details->entityClass,
                'collection_class'           => $details->collectionClass,
                'service_name'               => $details->serviceName,
            ],
        ]];
        $this->configResource->patch($config, true);
    }

    /**
     * Create content negotiation configuration based on payload and discovered
     * controller service name
     *
     * @param DoctrineRestServiceEntity $details
     * @param string $controllerService
     */
    public function createContentNegotiationConfig(DoctrineRestServiceEntity $details, $controllerService)
    {
        $config = [
            'controllers' => [
                $controllerService => $details->selector,
            ],
        ];
        $whitelist = $details->acceptWhitelist;
        if (! empty($whitelist)) {
            $config['accept-whitelist'] = [$controllerService => $whitelist];
        }
        $whitelist = $details->contentTypeWhitelist;
        if (! empty($whitelist)) {
            $config['content-type-whitelist'] = [$controllerService => $whitelist];
        }
        $config = ['zf-content-negotiation' => $config];
        $this->configResource->patch($config, true);
    }

    /**
     * Create Doctrine configuration
     *
     * @param DoctrineRestServiceEntity $details
     * @param string $entityClass
     * @param string $collectionClass
     * @param string $routeName
     */
    public function createDoctrineConfig(DoctrineRestServiceEntity $details, $entityClass, $collectionClass, $routeName)
    {
        $entityValue        = $details->getArrayCopy();
        $objectManager      = $this->getServiceManager()->get($details->objectManager);
        $hydratorStrategies = [];

        // The abstract_factories key is set to the value so these factories do not get duplicaed with each resource
        $config = [
            'zf-apigility' => [
                'doctrine-connected' => [
                    $details->resourceClass => [
                        'object_manager' => $details->objectManager,
                        'hydrator' => $details->hydratorName,
                    ],
                ],
            ],
        ];

        $this->configResource->patch($config, true);
    }

    /**
     * Create Doctrine hydrator configuration
     *
     * @param DoctrineRestServiceEntity $details
     * @param string $entityClass
     * @param string $collectionClass
     * @param string $routeName
     * @throws CreationException
     */
    public function createDoctrineHydratorConfig(
        DoctrineRestServiceEntity $details,
        $entityClass,
        $collectionClass,
        $routeName
    ) {
        $entityValue = $details->getArrayCopy();

        // Verify the object manager exists
        $objectManager      = $this->getServiceManager()->get($details->objectManager);
        $hydratorStrategies = isset($entityValue['strategies']) ? $entityValue['strategies'] : [];

        foreach ($hydratorStrategies as $strategy) {
            if (! $this->getServiceManager()->has($strategy)) {
                throw new CreationException('Invalid strategy specified. Must be declared in the service manager.');
            }
        }

        // The abstract_factories key is set to the value so these factories do not get duplicaed with each resource
        $config = [
            'doctrine-hydrator' => [
                $details->hydratorName => [
                    'entity_class'           => $entityClass,
                    'object_manager'         => $details->objectManager,
                    'by_value'               => $entityValue['by_value'],
                    'strategies'             => $hydratorStrategies,
                    'use_generated_hydrator' => $entityValue['use_generated_hydrator'],
                ],
            ],
        ];

        $this->configResource->patch($config, true);
    }

    /**
     * Create HAL configuration
     *
     * @param DoctrineRestServiceEntity $details
     * @param string $entityClass
     * @param string $collectionClass
     * @param string $routeName
     */
    public function createHalConfig(DoctrineRestServiceEntity $details, $entityClass, $collectionClass, $routeName)
    {
        $config = [
            'zf-hal' => [
                'metadata_map' => [
                    $entityClass => [
                        'route_identifier_name'  => $details->routeIdentifierName,
                        'entity_identifier_name' => $details->entityIdentifierName,
                        'route_name'             => $routeName,
                    ],
                    $collectionClass => [
                        'entity_identifier_name' => $details->entityIdentifierName,
                        'route_name'             => $routeName,
                        'is_collection'          => true,
                    ],
                ],
            ],
        ];

        if (isset($details->hydratorName)) {
            $config['zf-hal']['metadata_map'][$entityClass]['hydrator'] = $details->hydratorName;
        }

        $this->configResource->patch($config, true);
    }

    /**
     * Update the route for an existing service
     *
     * @param DoctrineRestServiceEntity $original
     * @param DoctrineRestServiceEntity $update
     */
    public function updateRoute(DoctrineRestServiceEntity $original, DoctrineRestServiceEntity $update)
    {
        $route = $update->routeMatch;
        if (! $route) {
            return;
        }

        $routeName = $original->routeName;
        $config = [
            'router' => [
                'routes' => [
                    $routeName => [
                        'options' => [
                            'route' => $route,
                        ],
                    ],
                ],
            ],
        ];

        $this->configResource->patch($config, true);
    }

    /**
     * Update REST configuration
     *
     * @param DoctrineRestServiceEntity $original
     * @param DoctrineRestServiceEntity $update
     */
    public function updateRestConfig(DoctrineRestServiceEntity $original, DoctrineRestServiceEntity $update)
    {
        $patch = [];
        foreach ($this->restScalarUpdateOptions as $property => $configKey) {
            if (! $update->$property) {
                continue;
            }
            $patch[$configKey] = $update->$property;
        }

        if (empty($patch)) {
            goto updateArrayOptions;
        }

        $config = ['zf-rest' => [
            $original->controllerServiceName => $patch,
        ]];
        $this->configResource->patch($config, true);

        updateArrayOptions:

        foreach ($this->restArrayUpdateOptions as $property => $configKey) {
            if ($update->$property === null) {
                continue;
            }
            $key = sprintf('zf-rest.%s.%s', $original->controllerServiceName, $configKey);
            $this->configResource->patchKey($key, $update->$property);
        }
    }

    /**
     * Update Doctrine hydrator configuration
     *
     * @param DoctrineRestServiceEntity $original
     * @param DoctrineRestServiceEntity $update
     */
    public function updateDoctrineHydratorConfig(DoctrineRestServiceEntity $original, DoctrineRestServiceEntity $update)
    {
        $patch = [];
        foreach ($this->doctrineHydratorOptions as $property => $configKey) {
            if ($update->$property === null) {
                continue;
            }
            $key = sprintf('doctrine-hydrator.%s.%s', $update->hydratorName, $configKey);
            $this->configResource->patchKey($key, $update->$property);
        }
    }

    /**
     * Update the content negotiation configuration for the service
     *
     * @param DoctrineRestServiceEntity $original
     * @param DoctrineRestServiceEntity $update
     */
    public function updateContentNegotiationConfig(
        DoctrineRestServiceEntity $original,
        DoctrineRestServiceEntity $update
    ) {
        $baseKey = 'zf-content-negotiation.';
        $service = $original->controllerServiceName;

        if ($update->selector) {
            $key = $baseKey . 'controllers.' . $service;
            $this->configResource->patchKey($key, $update->selector);
        }

        $acceptWhitelist = $update->acceptWhitelist;
        if (is_array($acceptWhitelist) && $acceptWhitelist) {
            $key = $baseKey . 'accept-whitelist.' . $service;
            $this->configResource->patchKey($key, $acceptWhitelist);
        }

        $contentTypeWhitelist = $update->contentTypeWhitelist;
        if (is_array($contentTypeWhitelist) && $contentTypeWhitelist) {
            $key = $baseKey . 'content-type-whitelist.' . $service;
            $this->configResource->patchKey($key, $contentTypeWhitelist);
        }
    }

    /**
     * Update Doctrine configuration
     *
     * @param DoctrineRestServiceEntity $original
     * @param DoctrineRestServiceEntity $update
     */
    public function updateDoctrineConfig(DoctrineRestServiceEntity $original, DoctrineRestServiceEntity $update)
    {
        $patch                   = [];
        $patch['object_manager'] = $update->objectManager;
        $patch['hydrator']       = $update->hydratorName;
        $basekey                 = 'zf-apigility.doctrine-connected.';
        $resource                = $update->resourceClass;

        $this->configResource->patchKey($basekey . $resource, $patch);
    }

    /**
     * Delete the files which were automatically created
     *
     * @param DoctrineRestServiceEntity $entity
     */
    public function deleteFiles(DoctrineRestServiceEntity $entity)
    {
        $config = $this->configResource->fetch(true);

        $restResourceClass   = $config['zf-rest'][$entity->controllerServiceName]['listener'];
        $restCollectionClass = $config['zf-rest'][$entity->controllerServiceName]['collection_class'];

        $reflector = new ReflectionClass($restResourceClass);
        unlink($reflector->getFileName());

        $reflector = new ReflectionClass($restCollectionClass);
        unlink($reflector->getFileName());
    }

    /**
     * Delete the route associated with the given service
     *
     * @param DoctrineRestServiceEntity $entity
     */
    public function deleteRoute(DoctrineRestServiceEntity $entity)
    {
        $config = $this->configResource->fetch(true);

        $route = $entity->routeName;
        $key   = ['router', 'routes', $route];
        $this->configResource->deleteKey($key);

        $uriKey = array_search($route, $config['zf-versioning']['uri']);
        if ($uriKey !== false) {
            $key = ['zf-versioning', 'uri', $uriKey];
            $this->configResource->deleteKey($key);
        }
    }

    /**
     * Delete the REST configuration associated with the given
     * service
     *
     * @param DoctrineRestServiceEntity $entity
     */
    public function deleteDoctrineRestConfig(DoctrineRestServiceEntity $entity)
    {
        // Get hydrator name
        $config = $this->configResource->fetch(true);
        $hydratorName = $config['zf-hal']['metadata_map'][$entity->entityClass]['hydrator'];
        $objectManagerClass = $config['doctrine-hydrator'][$hydratorName]['object_manager'];

        $key = ['doctrine-hydrator', $hydratorName];
        $this->configResource->deleteKey($key);

        $key = ['zf-apigility', 'doctrine-connected', $entity->resourceClass];
        $this->configResource->deleteKey($key);

        $key = ['zf-rest', $entity->controllerServiceName];
        $this->configResource->deleteKey($key);

        $key = ['zf-content-negotiation', 'controllers', $entity->controllerServiceName];
        $this->configResource->deleteKey($key);

        $key = ['zf-content-negotiation', 'accept-whitelist', $entity->controllerServiceName];
        $this->configResource->deleteKey($key);

        $key = ['zf-content-negotiation', 'content-type-whitelist', $entity->controllerServiceName];
        $this->configResource->deleteKey($key);

        $key = ['zf-hal', 'metadata_map', $entity->collectionClass];
        $this->configResource->deleteKey($key);

        $key = ['zf-hal', 'metadata_map', $entity->entityClass];
        $this->configResource->deleteKey($key);

        $validator = $config['zf-content-validation'][$entity->controllerServiceName]['input_filter'];

        $key = ['zf-content-validation', $entity->controllerServiceName];
        $this->configResource->deleteKey($key);

        $key = ['input_filter_specs', $validator];
        $this->configResource->deleteKey($key);
    }

    /**
     * Creates a class file based on the view model passed, the type of resource,
     * and writes it to the path provided.
     *
     * @param ViewModel $model
     * @param string $type
     * @param string $classPath
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
     * @param PhpRenderer $renderer
     * @param string $type
     * @return string Template name
     */
    protected function injectResolver(PhpRenderer $renderer, $type)
    {
        $template = sprintf('doctrine/rest-', $type);
        $path     = sprintf('%s/../../../view/doctrine/rest-%s.phtml', __DIR__, $type);
        $resolver = new Resolver\TemplateMapResolver([
            $template => $path,
        ]);
        $renderer->setResolver($resolver);

        return $template;
    }

    /**
     * Get the source path for the module
     *
     * @param string $resourceName
     * @return string
     */
    protected function getSourcePath($resourceName)
    {
        $sourcePath = $this->modules->getRestPath(
            $this->module,
            $this->moduleEntity->getLatestVersion(),
            $resourceName
        );

        if (! file_exists($sourcePath)) {
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
        $this->routeNameFilter
            ->attachByName(CamelCaseToDash::class)
            ->attachByName(StringToLower::class);

        return $this->routeNameFilter;
    }

    /**
     * Retrieve route information for a given service based on the configuration available
     *
     * @param DoctrineRestServiceEntity $metadata
     * @param array $config
     */
    protected function getRouteInfo(DoctrineRestServiceEntity $metadata, array $config)
    {
        $routeName = $metadata->routeName;
        if (! $routeName
            || ! isset($config['router']['routes'][$routeName]['options']['route'])
        ) {
            return;
        }

        $metadata->exchangeArray([
            'route_match' => $config['router']['routes'][$routeName]['options']['route'],
        ]);
    }

    /**
     * Merge the content negotiation configuration for the given controller
     * service into the REST metadata
     *
     * @param string $controllerServiceName
     * @param DoctrineRestServiceEntity $metadata
     * @param array $config
     */
    protected function mergeContentNegotiationConfig(
        $controllerServiceName,
        DoctrineRestServiceEntity $metadata,
        array $config
    ) {
        if (! isset($config['zf-content-negotiation'])) {
            return;
        }

        $config = $config['zf-content-negotiation'];

        if (isset($config['controllers'][$controllerServiceName])) {
            $metadata->exchangeArray([
                'selector' => $config['controllers'][$controllerServiceName],
            ]);
        }

        if (isset($config['accept-whitelist'][$controllerServiceName])) {
            $metadata->exchangeArray([
                'accept_whitelist' => $config['accept-whitelist'][$controllerServiceName],
            ]);
        }

        if (isset($config['content-type-whitelist'][$controllerServiceName])) {
            $metadata->exchangeArray([
                'content-type-whitelist' => $config['content-type-whitelist'][$controllerServiceName],
            ]);
        }
    }

    /**
     * Merge entity and collection class into metadata, if found
     *
     * @param string $controllerServiceName
     * @param DoctrineRestServiceEntity $metadata
     * @param array $config
     */
    protected function mergeHalConfig($controllerServiceName, DoctrineRestServiceEntity $metadata, array $config)
    {
        if (! isset($config['zf-hal']['metadata_map'])) {
            return;
        }

        $config = $config['zf-hal']['metadata_map'];

        $entityClass     = $this->deriveEntityClass($controllerServiceName, $metadata, $config);
        $collectionClass = $this->deriveCollectionClass($controllerServiceName, $metadata, $config);
        $merge           = [];

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
     * @param string $controllerServiceName
     * @param DoctrineRestServiceEntity $metadata
     * @param array $config
     * @return string
     */
    protected function deriveEntityClass($controllerServiceName, DoctrineRestServiceEntity $metadata, array $config)
    {
        if (isset($config['zf-rest'][$controllerServiceName]['entity_class'])) {
            return $config['zf-rest'][$controllerServiceName]['entity_class'];
        }

        $module = $metadata->module == $this->module ? $this->module : $metadata->module;
        $q = preg_quote('\\');
        if (! preg_match(
            sprintf(
                '#%s%sRest%s(?P<service>[^%s]+)%sController#',
                $module,
                $q,
                $q,
                $q,
                $q
            ),
            $controllerServiceName,
            $matches
        )) {
            return null;
        }

        return sprintf('%s\\Rest\\%s\\%sEntity', $module, $matches['service'], $matches['service']);
    }

    /**
     * Derive the name of the collection class from the controller service name
     *
     * @param string $controllerServiceName
     * @param DoctrineRestServiceEntity $metadata
     * @param array $config
     * @return string
     */
    protected function deriveCollectionClass($controllerServiceName, DoctrineRestServiceEntity $metadata, array $config)
    {
        if (isset($config['zf-rest'][$controllerServiceName]['collection_class'])) {
            return $config['zf-rest'][$controllerServiceName]['collection_class'];
        }

        $module = $metadata->module == $this->module ? $this->module : $metadata->module;
        if (! preg_match(
            '#'
            . preg_quote($module . '\\Rest\\')
            . '(?P<service>[^\\\\]+)'
            . preg_quote('\\Controller')
            . '#',
            $controllerServiceName,
            $matches
        )) {
            return null;
        }

        return sprintf('%s\\Rest\\%s\\%sCollection', $module, $matches['service'], $matches['service']);
    }
}
