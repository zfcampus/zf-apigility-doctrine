<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZF\Apigility\Doctrine\Admin\Model;

use Zend\Filter\FilterChain;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver;
use ZF\Apigility\Admin\Exception;
use ZF\Configuration\ConfigResource;
use ZF\Configuration\ModuleUtils;
use ZF\Rest\Exception\PatchException;
use ZF\Rest\Exception\CreationException;
use ZF\Apigility\Admin\Model\ModuleEntity;

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
     * @var ModuleUtils
     */
    protected $modules;

    /**
     * @param  string $module
     * @param  ModuleUtils $modules
     * @param  ConfigResource $config
     */
    public function __construct(ModuleEntity $moduleEntity, ModuleUtils $modules, ConfigResource $config)
    {
        $this->module         = $moduleEntity->getName();
        $this->moduleEntity   = $moduleEntity;
        $this->modules        = $modules;
        $this->configResource = $config;
    }

    /**
     * Fetch a single RPC service
     *
     * @todo   get route details?
     * @param  string $controllerServiceName
     * @return DoctrineRpcServiceEntity|false
     */
    public function fetch($controllerServiceName)
    {
        $data   = array('controller_service_name' => $controllerServiceName);
        $config = $this->configResource->fetch(true);
        if (isset($config['zf-rpc'])
            && isset($config['zf-rpc'][$controllerServiceName])
        ) {
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
            if (isset($contentNegotiationConfig['controllers'])
                && isset($contentNegotiationConfig['controllers'][$controllerServiceName])
            ) {
                $data['selector'] = $contentNegotiationConfig['controllers'][$controllerServiceName];
            }

            if (isset($contentNegotiationConfig['accept_whitelist'])
                && isset($contentNegotiationConfig['accept_whitelist'][$controllerServiceName])
            ) {
                $data['accept_whitelist'] = $contentNegotiationConfig['accept_whitelist'][$controllerServiceName];
            }

            if (isset($contentNegotiationConfig['content_type_whitelist'])
                && isset($contentNegotiationConfig['content_type_whitelist'][$controllerServiceName])
            ) {
                $data['content_type_whitelist'] = $contentNegotiationConfig['content_type_whitelist'][$controllerServiceName];
            }
        }

        $service = new DoctrineRpcServiceEntity();
        $service->exchangeArray($data);
        return $service;
    }

    /**
     * Fetch all services
     *
     * @return DoctrineRpcServiceEntity[]
     */
    public function fetchAll($version = null)
    {
        $config = $this->configResource->fetch(true);
        if (!isset($config['zf-rpc'])) {
            return array();
        }

        $services = array();
        $pattern  = false;

        // Initialize pattern if a version was passed and it's valid
        if (null !== $version) {
            if (!in_array($version, $this->moduleEntity->getVersions())) {
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

        foreach (array_keys($config['zf-rpc']) as $controllerService) {
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
     * Create a new RPC service in this module
     *
     * Creates the controller and all configuration, returning the full configuration as a tree.
     *
     * @todo   Return the controller service name
     * @param  string $serviceName
     * @param  string $route
     * @param  array $httpMethods
     * @param  null|string $selector
     * @return DoctrineRpcServiceEntity
     */
    public function createService($serviceName, $route, $httpMethods, $selector, $options)
    {
        $serviceName = ucfirst($serviceName);

        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*(\\\[a-zA-Z][a-zA-Z0-9_]*)*$/', $serviceName)) {
            /** @todo define exception in Rpc namespace */
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
     * @param  DoctrineRpcServiceEntity $entity
     * @return true
     */
    public function deleteService(DoctrineRpcServiceEntity $entity)
    {
        $serviceName = $entity->controllerServiceName;
        $routeName   = $entity->routeName;

        $this->deleteRouteConfig($routeName);
        $this->deleteRpcConfig($serviceName);
        $this->deleteContentNegotiationConfig($serviceName);
        return true;
    }

    /**
     * Create a controller in the current module named for the given service
     *
     * @param  string $serviceName
     * @return stdClass
     */
    public function createController($serviceName)
    {
        $module     = $this->module;
        $modulePath = $this->modules->getModulePath($module);
        $version    = $this->moduleEntity->getLatestVersion();

        $srcPath = sprintf(
            '%s/src/%s/V%s/Rpc/%s',
            $modulePath,
            str_replace('\\', '/', $module),
            $version,
            $serviceName
        );

        if (!file_exists($srcPath)) {
            mkdir($srcPath, 0777, true);
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

        $view = new ViewModel(array(
            'module'      => $module,
            'classname'   => $className,
            'servicename' => $serviceName,
            'version'     => $version,
        ));

        $resolver = new Resolver\TemplateMapResolver(array(
            'code-connected/rpc-controller' => __DIR__ . '/../../../../../../view/doctrine/rpc-controller.phtml'
        ));

        $view->setTemplate('code-connected/rpc-controller');
        $renderer = new PhpRenderer();
        $renderer->setResolver($resolver);

        if (!file_put_contents($classPath,
            "<?php\n" . $renderer->render($view))) {
            return false;
        }

        $fullClassName = sprintf('%s\\V%s\\Rpc\\%s\\%s', $module, $version, $serviceName, $className);
        $this->configResource->patch(array(
            'controllers' => array(
                'invokables' => array(
                    $controllerService => $fullClassName,
                ),
            ),
        ), true);

        return (object) array(
            'class'   => $fullClassName,
            'file'    => $classPath,
            'service' => $controllerService,
        );
    }

    /**
     * Create the route configuration
     *
     * @param  string $route
     * @param  string $serviceName
     * @param  string $controllerService
     * @return string The newly created route name
     */
    public function createRoute($route, $serviceName, $controllerService = null)
    {
        if (null === $controllerService) {
            $controllerService = sprintf('%s\\Rpc\\%s\\Controller', $this->module, $serviceName);
        }

        $routeName = sprintf('%s.rpc.%s', $this->normalize($this->module), $this->normalize($serviceName));
        $action    = 'index'; # lcfirst($serviceName);

        $config = array(
            'router' => array(
                'routes' => array(
                    $routeName => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => $route,
                            'defaults' => array(
                                'controller' => $controllerService,
                                'action'     => $action,
                            ),
                        ),
                    ),
                )
            ),
            'zf-versioning' => array(
                'uri' => array (
                    $routeName
                )
            )
        );

        $this->configResource->patch($config, true);
        return $routeName;
    }

    /*
     * Create the zf-rpc configuration for the controller service
     *
     * @param  string $controllerService
     * @param  string $routeName
     * @param  array $httpMethods
     * @param  null|string|callable $callable
     * @return array
     */
    public function createDoctrineRpcConfig($controllerService, $options)
    {
        $config = array('zf-rpc-doctrine-controller' => array(
            $controllerService => $options
        ));

        return $this->configResource->patch($config, true);
    }

    /*
     * Create the zf-rpc configuration for the controller service
     *
     * @param  string $controllerService
     * @param  string $routeName
     * @param  array $httpMethods
     * @param  null|string|callable $callable
     * @return array
     */
    public function createRpcConfig($controllerService, $routeName, array $httpMethods = array('GET'), $callable = null)
    {
        $config = array('zf-rpc' => array(
            $controllerService => array(
                'http_methods' => $httpMethods,
                'route_name'   => $routeName,
            ),
        ));
        if (null !== $callable) {
            $config[$controllerService]['callable'] = $callable;
        }
        return $this->configResource->patch($config, true);
    }

    /**
     * Create the selector configuration
     *
     * @param  string $controllerService
     * @param  string $selector
     * @return array
     */
    public function createContentNegotiationConfig($controllerService, $selector = null)
    {
        if (null === $selector) {
            $selector = 'Json';
        }

        $config = array('zf-content-negotiation' => array(
            'controllers' => array(
                $controllerService => $selector,
            ),
            'accept_whitelist' => array(
                $controllerService => array(
                    'application/json',
                    'application/*+json',
                ),
            ),
            'content_type_whitelist' => array(
                $controllerService => array(
                    'application/json',
                ),
            ),
        ));
        return $this->configResource->patch($config, true);
    }

    /**
     * Update the route associated with a controller service
     *
     * @param  string $controllerService
     * @param  string $routeMatch
     * @return true
     */
    public function updateRoute($controllerService, $routeMatch)
    {
        $services  = $this->fetch($controllerService);
        if (!$services) {
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
     * @param  string $controllerService
     * @param  array $httpMethods
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
     * @param  string $controllerService
     * @param  string $selector
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
     * @param  string $controllerService
     * @param  string $headerType
     * @param  array $whitelist
     * @return true
     */
    public function updateContentNegotiationWhitelist($controllerService, $headerType, array $whitelist)
    {
        if (!in_array($headerType, array('accept', 'content_type'))) {
            /** @todo define exception in Rpc namespace */
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
     * @param  string $routeName
     */
    public function deleteRouteConfig($routeName)
    {
        $key = array('router', 'routes', $routeName);
        $this->configResource->deleteKey($key);
    }

    /**
     * Delete the RPC configuration for a named RPC service
     *
     * @param  string $serviceName
     */
    public function deleteRpcConfig($serviceName)
    {
        $key = array('zf-rpc', $serviceName);
        $this->configResource->deleteKey($key);
    }

    /**
     * Delete the Content Negotiation configuration for a named RPC
     * service
     *
     * @param  string $serviceName
     */
    public function deleteContentNegotiationConfig($serviceName)
    {
        $key = array('zf-content-negotiation', 'controllers', $serviceName);
        $this->configResource->deleteKey($key);

        $key = array('zf-content-negotiation', 'accept_whitelist', $serviceName);
        $this->configResource->deleteKey($key);

        $key = array('zf-content-negotiation', 'content_type_whitelist', $serviceName);
        $this->configResource->deleteKey($key);
    }

    /**
     * Normalize a service or module name to lowercase, dash-separated
     *
     * @param  string $string
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
     * @param  string $routeName
     * @param  array $config
     * @return false|string
     */
    protected function getRouteMatchStringFromModuleConfig($routeName, array $config)
    {
        if (!isset($config['router'])
            || !isset($config['router']['routes'])
        ) {
            return false;
        }

        $config = $config['router']['routes'];
        if (!isset($config[$routeName])
            || !is_array($config[$routeName])
        ) {
            return false;
        }

        $config = $config[$routeName];

        if (!isset($config['options'])
            || !isset($config['options']['route'])
        ) {
            return false;
        }

        return $config['options']['route'];
    }
}
