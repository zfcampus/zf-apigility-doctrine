<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace Apigility\Doctrine\Admin\Model;

use Zend\Filter\FilterChain;

class DoctrineRestServiceEntity
{
    protected $filters = array();

    protected $acceptWhitelist = array(
        'application/json',
        'application/*+json',
    );

    protected $collectionClass;

    protected $collectionHttpMethods = array('GET', 'POST');

    protected $collectionName;

    protected $collectionQueryWhitelist = array();

    protected $contentTypeWhitelist = array(
        'application/json',
    );

    protected $controllerServiceName;

    protected $entityClass;

    protected $routeIdentifierName;

    protected $entityIdentifierName;

    protected $module;

    protected $pageSize = 25;

    protected $pageSizeParam;

    protected $resourceClass;

    protected $resourceHttpMethods = array('GET', 'PATCH', 'PUT', 'DELETE');

    protected $routeMatch;

    protected $routeName;

    protected $selector = 'HalJson';

    protected $hydratorName;

    protected $objectManager;

    protected $hydrateByValue;

    public function __get($name)
    {
        if ($name === 'filter') {
            throw new \OutOfRangeException(sprintf(
                '%s does not contain a property by the name of "%s"',
                __CLASS__,
                $name
            ));
        }
        if (!property_exists($this, $name)) {
            throw new \OutOfRangeException(sprintf(
                '%s does not contain a property by the name of "%s"',
                __CLASS__,
                $name
            ));
        }
        return $this->{$name};
    }

    public function __isset($name)
    {
        if ($name === 'filter') {
            return false;
        }
        return (property_exists($this, $name));
    }

    public function exchangeArray(array $data)
    {
        foreach ($data as $key => $value) {
            $key = strtolower($key);
            $key = str_replace('_', '', $key);
            switch ($key) {
                case 'acceptwhitelist':
                    $this->acceptWhitelist = $value;
                    break;
                case 'collectionclass':
                    $this->collectionClass = $value;
                    break;
                case 'collectionhttpmethods':
                    $this->collectionHttpMethods = $value;
                    break;
                case 'collectionname':
                    $this->collectionName = $value;
                    break;
                case 'collectionquerywhitelist':
                    $this->collectionQueryWhitelist = $value;
                    break;
                case 'contenttypewhitelist':
                    $this->contentTypeWhitelist = $value;
                    break;
                case 'controllerservicename':
                    $this->controllerServiceName = $value;
                    break;
                case 'entityclass':
                    $this->entityClass = $value;
                    break;
                case 'routeidentifiername':
                    $this->routeIdentifierName = $value;
                    break;
                case 'module':
                    $this->module = $value;
                    break;
                case 'pagesize':
                    $this->pageSize = $value;
                    break;
                case 'pagesizeparam':
                    $this->pageSizeParam = $value;
                    break;
                case 'resourceclass':
                    $this->resourceClass = $value;
                    break;
                case 'resourcehttpmethods':
                    $this->resourceHttpMethods = $value;
                    break;
                case 'routematch':
                    $this->routeMatch = $value;
                    break;
                case 'routename':
                    $this->routeName = $value;
                    break;
                case 'selector':
                    $this->selector = $value;
                    break;
                case 'hydratorname':
                    $this->hydratorName = $value;
                    break;
                case 'objectmanager':
                    $this->objectManager = $value;
                    break;
                case 'hydratebyvalue':
                    $this->hydrateByValue = $value;
                    break;
                case 'entityidentifiername':
                    $this->entityIdentifierName = $value;
                    break;
            }
        }
    }

    public function getArrayCopy()
    {
        return array(
            'accept_whitelist'           => $this->acceptWhitelist,
            'collection_class'           => $this->collectionClass,
            'collection_http_methods'    => $this->collectionHttpMethods,
            'collection_name'            => $this->collectionName,
            'collection_query_whitelist' => $this->collectionQueryWhitelist,
            'content_type_whitelist'     => $this->contentTypeWhitelist,
            'controller_service_name'    => $this->controllerServiceName,
            'entity_class'               => $this->entityClass,
            'route_identifier_name'      => $this->routeIdentifierName,
            'entity_identifier_name'     => $this->entityIdentifierName,
            'module'                     => $this->module,
            'page_size'                  => $this->pageSize,
            'page_size_param'            => $this->pageSizeParam,
            'resource_class'             => $this->resourceClass,
            'resource_http_methods'      => $this->resourceHttpMethods,
            'route_match'                => $this->routeMatch,
            'route_name'                 => $this->routeName,
            'selector'                   => $this->selector,
            'hydrator_name'              => $this->hydratorName,
            'object_manager'             => $this->objectManager,
            'hydrate_by_value'           => $this->hydrateByValue,
        );
    }

    protected function normalizeResourceNameForIdentifier($resourceName)
    {
        return $this->getIdentifierNormalizationFilter()->filter($resourceName);
    }

    protected function normalizeResourceNameForRoute($resourceName)
    {
        return $this->getRouteNormalizationFilter()->filter($resourceName);
    }

    /**
     * Retrieve and/or initialize the normalization filter chain for identifiers
     *
     * @return FilterChain
     */
    protected function getIdentifierNormalizationFilter()
    {
        if (isset($this->filters['identifier'])
            && $this->filters['identifier'] instanceof FilterChain
        ) {
            return $this->filters['identifier'];
        }
        $filter = new FilterChain();
        $filter->attachByName('WordCamelCaseToUnderscore')
               ->attachByName('StringToLower');
        $this->filters['identifier'] = $filter;
        return $filter;
    }

    /**
     * Retrieve and/or initialize the normalization filter chain
     *
     * @return FilterChain
     */
    protected function getRouteNormalizationFilter()
    {
        if (isset($this->filters['route'])
            && $this->filters['route'] instanceof FilterChain
        ) {
            return $this->filters['route'];
        }
        $filter = new FilterChain();
        $filter->attachByName('WordCamelCaseToDash')
               ->attachByName('StringToLower');
        $this->filters['route'] = $filter;
        return $filter;
    }
}
