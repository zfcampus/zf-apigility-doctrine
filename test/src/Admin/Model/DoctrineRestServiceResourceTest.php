<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\Apigility\Doctrine\Admin\Model;

use Doctrine\ORM\Tools\SchemaTool;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use ZFTest\Util\ServiceManagerFactory;

class DoctrineRestServiceResourceTest extends AbstractHttpControllerTestCase
{
    public function setUp()
    {
        $this->setApplicationConfig(
            include __DIR__ . '/../../../../../config/application.config.php'
        );
        parent::setUp();
    }

    public function tearDown()
    {
        # FIXME: Drop database from in-memory
    }

    /**
     * @see https://github.com/zfcampus/zf-apigility/issues/18
     */
    public function testCreateReturnsRestServiceEntityWithControllerServiceNamePopulated()
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');

        $tool = new SchemaTool($em);
        $res = $tool->createSchema($em->getMetadataFactory()->getAllMetadata());

        // Create DB
        $resourceDefinition = [
            "objectManager" => "doctrine.entitymanager.orm_default",
            "serviceName" => "Artist",
            "entityClass" => "Db\\Entity\\Artist",
            "routeIdentifierName" => "artist_id",
            "entityIdentifierName" => "id",
            "routeMatch" => "/db-test/artist",
        ];

        // Verify ORM is working
        $artist = new \Db\Entity\Artist;
        $artist->setName('TestInsert');
        $artist->setCreatedAt(new \Datetime());
        $em->persist($artist);
        $em->flush();
        $found = $em->getRepository('Db\Entity\Artist')->find($artist->getId());
        $this->assertInstanceOf('Db\Entity\Artist', $found);

        $this->resource = $serviceManager->get('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceResource');
        $this->resource->setModuleName('DbApi');

        $entity = $this->resource->create($resourceDefinition);
        $this->assertInstanceOf('ZF\Apigility\Doctrine\Admin\Model\DoctrineRestServiceEntity', $entity);
        $controllerServiceName = $entity->controllerServiceName;
        $this->assertNotEmpty($controllerServiceName);
        $this->assertContains('DbApi\V1\Rest\Artist\Controller', $controllerServiceName);

        //        $serviceManager = ServiceManagerFactory::getServiceManager();
        //        $config = $serviceManager->get('Config');

        //        $routerConfig = isset($config['router']) ? $config['router'] : [];
        //        $router = HttpRouter::factory($routerConfig);

        //        $routeMatch = new RouteMatch(['controller' => $controllerServiceName]);
        //        $event = new MvcEvent();
        //        $event->setRouter($router);
        //        $event->setRouteMatch($routeMatch);

        //        $this->getRequest()->setMethod('GET');

        $request = $this->getRequest();
        $request->setMethod('GET');
        $request->getHeaders()->addHeaders(
            [
            'Accept' => 'application/json',
            ]
        );

        $x = $this->dispatch('/db-api/artist');

        $this->resource->delete('DbApi\\V1\\Rest\\Artist\\Controller');

        print_r($x);

        return;

        //        $controller->setEvent($event);
        //        $controller->setServiceLocator($serviceManager);

        //        $routeMatch = new RouteMatch(['controller' => $controllerServiceName]);

        //        print_r($config);
        //        print_r(get_class_methods($router));

        $this->resource->delete('DbApi\\V1\\Rest\\Artist\\Controller');

        return;

        //        $controller = new $controllerServiceName;
        //        $request    = new Request();

        $query = [];
        $query[] = ['type' => 'eq', 'field' => 'id', 'value' => $found->getId()];

        // Fetch test runs
        $routeMatch->setParam('action', 'index');

        $result   = $controller->dispatch($this->request);
        $response = $controller->getResponse();

        //        $this->assertEquals(200, $response->getStatusCode());

        $hal = $response->getBody();

        $renderer = $this->getServiceLocator()->get('ZF\Hal\JsonRenderer');
        $data = json_decode($renderer->render($hal), true);

        print_r($data);
    }
}
