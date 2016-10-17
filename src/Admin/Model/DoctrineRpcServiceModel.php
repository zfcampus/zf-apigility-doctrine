<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use Zend\Filter\FilterChain;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver;
use ZF\Apigility\Admin\Exception;
use ZF\Apigility\Admin\Model\ModuleEntity;
use ZF\Apigility\Admin\Model\ModulePathSpec;
use ZF\Configuration\ConfigResource;
use ZF\Rest\Exception\CreationException;
use ZF\Rest\Exception\PatchException;

class DoctrineRpcServiceModel
{
    /**
     * @var ConfigResource
     */
    protected $configResource;

    /**
     * @var FilterChain
     */
    protected $filter;

    /**
     * @var string
     */
    protected $module;

    /**
     * @var ModuleEntity
     */
    protected $moduleEntity;

    /**
     * @var ModulePathSpec
     */
    protected $modules;

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
    }

    /**
     * Fetch a single RPC service
     *
     * @todo get route details?
     * @param string $controllerServiceName
     * @return DoctrineRpcServiceEntity|false
     */
    public function fetch($controllerServiceName)
    {
        $data   = ['controller_service_name' => $controllerServiceName];
        $config = $this->configResource->fetch(true);
        if (isset($config['zf-rpc'][$controllerServiceName])) {
            $rpcConfig = $config['zf-rpc'][$controllerServiceName];
            if (isset($rpcConfig['route_name'])) {
                $data['route_name']  = $rpcConfig['route_name'];
                $data['route_match'] = $this->getRouteMatchStringFromModuleConfig($data['route_name'], $config);
            }
            if (isset($rpcConfig['http_methods'])) {
                $data['http_methods'] = $rpcConfig['http_methods'];
            }
        } else {
            return false;
        }

        if (isset($config['zf-content-negotiation'])) {
            $contentNegotiationConfig = $config['zf-content-negotiation'];
            if (isset($contentNegotiationConfig['controllers'][$controllerServiceName])) {
                $data['selector'] = $contentNegotiationConfig['controllers'][$controllerServiceName];
            }

            if (isset($contentNegotiationConfig['accept_whitelist'][$controllerServiceName])) {
                // Is this handled differently in recent versions of Apigility // FIXME: verify this\
                $data['accept_whitelist'] = $contentNegotiationConfig['accept_whitelist'][$controllerServiceName];
            }

            if (isset($contentNegotiationConfig['content_type_whitelist'][$controllerServiceName])) {
                // Is this handled differently in recent versions of Apigility // FIXME: verify this\
                $data['content_type_whitelist'] =
                    $contentNegotiationConfig['content_type_whitelist'][$controllerServiceName];
            }
        }

        $service = new DoctrineRpcServiceEntity();
        $service->exchangeArray($data);

        return $service;
    }

    /**
     * Fetch all services
     *
     * @param string $version
     * @return DoctrineRpcServiceEntity[]
     */
    public function fetchAll($version = null)
    {
        $config = $this->configResource->fetch(true);
        if (! isset($config['zf-rpc-doctrine-controller'])) {
            return [];
        }

        $services = [];
        $pattern  = false;

        // Initialize pattern if a version was passed and it's valid
        // Ignored from code coverage because Apigility sets the version
        // and it's no longer handled here: FIXME: verify this
        if (null !== $version) {
            if (! in_array($version, $this->moduleEntity->getVersions())) {
                throw new Exception\RuntimeException(
                    sprintf('Invalid version "%s" provided', $version),
                    400
                );
            }
            $namespaceSep = preg_quote('\\');
            $pattern = sprintf(
                '#%s%sV%s#',
                $this->module,
                $namespaceSep,
                $version
            );
        }

        foreach (array_keys($config['zf-rpc-doctrine-controller']) as $controllerService) {
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
     * Create a new RPC service in this module
     *
     * Creates the controller and all configuration, returning the full configuration as a tree.
     *
     * @todo Return the controller service name
     * @param string $serviceName
     * @param string $route
     * @param array $httpMethods
     * @param null|string $selector
     * @return DoctrineRpcServiceEntity
     */
    public function createService($serviceName, $route, $httpMethods, $selector, $options)
    {
        $serviceName = ucfirst($serviceName);

        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_]*(\\\[a-zA-Z][a-zA-Z0-9_]*)*$/', $serviceName)) {
            /* @todo define exception in Rpc namespace */
            throw new CreationException('Invalid service name; must be a valid PHP namespace name.');
        }

        $controllerData    = $this->createController($serviceName);
        $controllerService = $controllerData->service;
        $routeName         = $this->createRoute($route, $serviceName, $controllerService);
        $this->createRpcConfig($controllerService, $routeName, $httpMethods);
        $this->createContentNegotiationConfig($controllerService, $selector);
        $this->createDoctrineRpcConfig($controllerService, $options);

        return $this->fetch($controllerService);
    }

    /**
     * Delete a service
     *
     * @param DoctrineRpcServiceEntity $entity
     * @return true
     */
    public function deleteService(DoctrineRpcServiceEntity $entity, $deleteFiles = true)
    {
        $serviceName = $entity->controllerServiceName;
        $routeName   = $entity->routeName;

        if ($deleteFiles) {
            $this->deleteFiles($entity);
        }
        $this->deleteRouteConfig($routeName);
        $this->deleteDoctrineRpcConfig($serviceName);
        $this->deleteContentNegotiationConfig($serviceName);

        return true;
    }

    /**
     * Delete the files which were automatically created
     *
     * @param DoctrineRpcServiceEntity $entity
     */
    public function deleteFiles(DoctrineRpcServiceEntity $entity)
    {
        $config = $this->configResource->fetch(true);

        $reflector = new \ReflectionClass($entity->controllerClass);
        unlink($reflector->getFileName());
    }

    /**
     * Create a controller in the current module named for the given service
     *
     * @param string $serviceName
     * @return mixed
     */
    public function createController($serviceName)
    {
        $module     = $this->module;
        $version    = $this->moduleEntity->getLatestVersion();
        $serviceName = str_replace("\\", "/", $serviceName);

        $srcPath = $this->modules->getRpcPath($module, $version, $serviceName);

        if (! file_exists($srcPath)) {
            mkdir($srcPath, 0775, true);
        }

        $className         = sprintf('%sController', $serviceName);
        $classPath         = sprintf('%s/%s.php', $srcPath, $className);
        $controllerService = sprintf('%s\\V%s\\Rpc\\%s\\Controller', $module, $version, $serviceName);

        if (file_exists($classPath)) {
            throw new Exception\RuntimeException(sprintf(
                'The controller "%s" already exists',
                $className
            ));
        }

        $view = new ViewModel([
            'module'      => $module,
            'classname'   => $className,
            'servicename' => $serviceName,
            'version'     => $version,
        ]);

        $resolver = new Resolver\TemplateMapResolver([
            'code-connected/rpc-controller' => __DIR__ . '/../../../view/doctrine/rpc-controller.phtml',
        ]);

        $view->setTemplate('code-connected/rpc-controller');
        $renderer = new PhpRenderer();
        $renderer->setResolver($resolver);

        if (! file_put_contents(
            $classPath,
            "<" . "?php\n" . $renderer->render($view)
        )) {
            return false;
        }

        $fullClassName = sprintf('%s\\V%s\\Rpc\\%s\\%s', $module, $version, $serviceName, $className);
        $this->configResource->patch(
            [
                'controllers' => [
                    'aliases' => [
                        $controllerService => $fullClassName,
                    ],
                    'factories' => [
                        $fullClassName => InvokableFactory::class,
                    ],
                ],
            ],
            true
        );

        return (object) [
            'class'   => $fullClassName,
            'file'    => $classPath,
            'service' => $controllerService,
        ];
    }

    /**
     * Create the route configuration
     *
     * @param string $route
     * @param string $serviceName
     * @param string $controllerService
     * @return string The newly created route name
     */
    public function createRoute($route, $serviceName, $controllerService = null)
    {
        if (null === $controllerService) {
            $controllerService = sprintf('%s\\Rpc\\%s\\Controller', $this->module, $serviceName);
        }

        $routeName = sprintf('%s.rpc.%s', $this->normalize($this->module), $this->normalize($serviceName));
        $action    = 'index';

        $config = [
            'router' => [
                'routes' => [
                    $routeName => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => $route,
                            'defaults' => [
                                'controller' => $controllerService,
                                'action'     => $action,
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
     * Create the zf-rpc configuration for the controller service
     *
     * @param $controllerService
     * @param $options
     * @return array
     */
    public function createDoctrineRpcConfig($controllerService, $options)
    {
        $config = ['zf-rpc-doctrine-controller' => [
            $controllerService => $options,
        ]];

        return $this->configResource->patch($config, true);
    }

    /**
     * Create the zf-rpc configuration for the controller service
     *
     * @param string $controllerService
     * @param string $routeName
     * @param array $httpMethods
     * @param null|string|callable $callable
     * @return array
     */
    public function createRpcConfig($controllerService, $routeName, array $httpMethods = ['GET'], $callable = null)
    {
        $config = [
            'zf-rpc' => [
                $controllerService => [
                    'http_methods' => $httpMethods,
                    'route_name'   => $routeName,
                ],
            ],
        ];

        if (null !== $callable) {
            $config[$controllerService]['callable'] = $callable;
        }

        return $this->configResource->patch($config, true);
    }

    /**
     * Create the selector configuration
     *
     * @param string $controllerService
     * @param string $selector
     * @return array
     */
    public function createContentNegotiationConfig($controllerService, $selector = null)
    {
        if (null === $selector) {
            $selector = 'Json';
        }

        $config = [
            'zf-content-negotiation' => [
                'controllers' => [
                    $controllerService => $selector,
                ],
                'accept-whitelist' => [
                    $controllerService => [
                        'application/json',
                        'application/*+json',
                    ],
                ],
                'content-type-whitelist' => [
                    $controllerService => [
                        'application/json',
                    ],
                ],
            ],
        ];

        return $this->configResource->patch($config, true);
    }

    /**
     * Update the route associated with a controller service
     *
     * @param string $controllerService
     * @param string $routeMatch
     * @return true
     */
    public function updateRoute($controllerService, $routeMatch)
    {
        $services = $this->fetch($controllerService);
        if (! $services) {
            return false;
        }

        $services  = $services->getArrayCopy();
        $routeName = $services['route_name'];

        $config = $this->configResource->fetch(true);
        $config['router']['routes'][$routeName]['options']['route'] = $routeMatch;

        $this->configResource->overwrite($config);

        return true;
    }

    /**
     * Update the allowed HTTP methods for a given service
     *
     * @param string $controllerService
     * @param array $httpMethods
     * @return true
     */
    public function updateHttpMethods($controllerService, array $httpMethods)
    {
        $config = $this->configResource->fetch(true);
        $config['zf-rpc'][$controllerService]['http_methods'] = $httpMethods;
        $this->configResource->overwrite($config);

        return true;
    }

    /**
     * Update the content-negotiation selector for the given service
     *
     * @param string $controllerService
     * @param string $selector
     * @return true
     */
    public function updateSelector($controllerService, $selector)
    {
        $config = $this->configResource->fetch(true);
        $config['zf-content-negotiation']['controllers'][$controllerService] = $selector;
        $this->configResource->overwrite($config);

        return true;
    }

    /**
     * Update configuration for a content negotiation whitelist for a named controller service
     *
     * @param string $controllerService
     * @param string $headerType
     * @param array $whitelist
     * @return true
     */
    public function updateContentNegotiationWhitelist($controllerService, $headerType, array $whitelist)
    {
        if (! in_array($headerType, ['accept', 'content_type'])) {
            /* @todo define exception in Rpc namespace */
            throw new PatchException('Invalid content negotiation whitelist type provided', 422);
        }

        $headerType .= '_whitelist';
        $config = $this->configResource->fetch(true);
        $config['zf-content-negotiation'][$headerType][$controllerService] = $whitelist;
        $this->configResource->overwrite($config);

        return true;
    }

    /**
     * Removes the route configuration for a named route
     *
     * @param string $routeName
     */
    public function deleteRouteConfig($routeName)
    {
        $config = $this->configResource->fetch(true);

        $key = ['router', 'routes', $routeName];
        $this->configResource->deleteKey($key);

        $key = ['zf-versioning', 'uri', array_search($routeName, $config['zf-versioning']['uri'])];
        $this->configResource->deleteKey($key);
    }

    /**
     * Delete the RPC configuration for a named RPC service
     *
     * @param string $serviceName
     */
    public function deleteDoctrineRpcConfig($serviceName)
    {
        $key = ['zf-rpc', $serviceName];
        $this->configResource->deleteKey($key);

        $key = ['zf-rpc-doctrine-controller', $serviceName];
        $this->configResource->deleteKey($key);

        $config = $this->configResource->fetch();
        if (isset($config['controllers.aliases.' . $serviceName])) {
            $fullClassName = $config['controllers.aliases.' . $serviceName];

            $key = ['controllers', 'aliases', $serviceName];
            $this->configResource->deleteKey($key);

            $key = ['controllers', 'factories', $fullClassName];
            $this->configResource->deleteKey($key);
        }

        $key = ['controllers', 'invokables', $serviceName];
        $this->configResource->deleteKey($key);

        $key = ['zf-content-negotiation', 'accept_whitelist', $serviceName];
        $this->configResource->deleteKey($key);

        $key = ['zf-content-negotiation', 'content_type_whitelist', $serviceName];
        $this->configResource->deleteKey($key);
    }

    /**
     * Delete the Content Negotiation configuration for a named RPC
     * service
     *
     * @param string $serviceName
     */
    public function deleteContentNegotiationConfig($serviceName)
    {
        $key = ['zf-content-negotiation', 'controllers', $serviceName];
        $this->configResource->deleteKey($key);

        $key = ['zf-content-negotiation', 'accept-whitelist', $serviceName];
        $this->configResource->deleteKey($key);

        $key = ['zf-content-negotiation', 'content-type-whitelist', $serviceName];
        $this->configResource->deleteKey($key);
    }

    /**
     * Normalize a service or module name to lowercase, dash-separated
     *
     * @param string $string
     * @return string
     */
    protected function normalize($string)
    {
        $filter = $this->getNormalizationFilter();
        $string = str_replace('\\', '-', $string);

        return $filter->filter($string);
    }

    /**
     * Retrieve and/or initialize the normalization filter chain
     *
     * @return FilterChain
     */
    protected function getNormalizationFilter()
    {
        if ($this->filter instanceof FilterChain) {
            return $this->filter;
        }
        $this->filter = new FilterChain();
        $this->filter->attachByName('WordCamelCaseToDash')
            ->attachByName('StringToLower');

        return $this->filter;
    }

    /**
     * Retrieve the URL match for the given route name
     *
     * @param string $routeName
     * @param array $config
     * @return false|string
     */
    protected function getRouteMatchStringFromModuleConfig($routeName, array $config)
    {
        if (! isset($config['router']['routes'])) {
            return false;
        }

        $config = $config['router']['routes'];
        if (! isset($config[$routeName])
            || ! is_array($config[$routeName])
        ) {
            return false;
        }

        $config = $config[$routeName];

        if (! isset($config['options']['route'])) {
            return false;
        }

        return $config['options']['route'];
    }
}
