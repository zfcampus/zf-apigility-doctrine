<?php

namespace ZFTest\Apigility\Doctrine\Admin\Server\ORM;

use Doctrine\ORM\Tools\SchemaTool;
use Zend\Http\Request;
use Db\Entity\Artist as ArtistEntity;

class CollectionFilters extends \Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase
{
    public function setUp()
    {
        $this->setApplicationConfig(
            include __DIR__ . '/../../../../../config/ORM/application.config.php'
        );
        parent::setUp();

        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');

        $tool = new SchemaTool($em);
        $res = $tool->createSchema($em->getMetadataFactory()->getAllMetadata());

        $this->getRequest()->getHeaders()->addHeaders(array(
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ));

        $this->getRequest()->setMethod(Request::METHOD_POST);

        $this->getRequest()->setContent('{"name": "ArtistOne","createdAt": "2011-12-18 13:17:17"}');
        $this->dispatch('/test/artist');

        $this->getRequest()->setContent('{"name": "ArtistTwo","createdAt": "2014-12-18 13:17:17"}');
        $this->dispatch('/test/artist');

        $this->getRequest()->setContent('{"name": "ArtistThree","createdAt": "2012-12-18 13:17:17"}');
        $this->dispatch('/test/artist');

        $this->getRequest()->setContent('{"name": "ArtistFour"}');
        $this->dispatch('/test/artist');

        $this->getRequest()->setMethod(Request::METHOD_GET);
        $this->getRequest()->setContent(null);
    }

    public function testEquals()
    {
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'type'=>'eq', 'value' => 'ArtistOne'),
                ),
            )
        );

        $this->dispatch("/test/artist?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(1, $body['count']);
    }

    public function testNotEquals()
    {
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'type'=>'neq', 'value' => 'ArtistOne'),
                ),
            )
        );

        $this->dispatch("/test/artist?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(3, $body['count']);
    }

    public function testLessThan()
    {
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'type'=>'lt', 'value' => '2014-01-01', 'format' => 'Y-m-d'),
                ),
            )
        );

        $this->dispatch("/test/artist?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(2, $body['count']);
    }

    public function testLessThanOrEquals()
    {
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'type'=>'lte', 'value' => '2011-12-18 13:17:17'),
                ),
            )
        );

        $this->dispatch("/test/artist?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(1, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'type'=>'lte', 'value' => '2011-12-18 13:17:16'),
                ),
            )
        );

        $this->dispatch("/test/artist?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(0, $body['count']);
    }

    public function testGreaterThan()
    {
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'type'=>'gt', 'value' => '2014-01-01', 'format' => 'Y-m-d'),
                ),
            )
        );

        $this->dispatch("/test/artist?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(1, $body['count']);
    }

    public function testGreaterThanOrEquals()
    {
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'type'=>'gte', 'value' => '2014-12-18 13:17:17'),
                ),
            )
        );

        $this->dispatch("/test/artist?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(1, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'type'=>'gte', 'value' => '2014-12-18 13:17:18'),
                ),
            )
        );

        $this->dispatch("/test/artist?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(0, $body['count']);
    }

    public function testIsNull()
    {
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'type'=>'isnull'),
                ),
            )
        );

        $this->dispatch("/test/artist?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(1, $body['count']);
    }

    public function testIsNotNull()
    {
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'type'=>'isnotnull'),
                ),
            )
        );

        $this->dispatch("/test/artist?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(3, $body['count']);
    }

    public function testIn()
    {
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'type'=>'in', 'values' => array('ArtistOne', 'ArtistTwo')),
                ),
            )
        );

        $this->dispatch("/test/artist?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(2, $body['count']);
    }

    public function testNotIn()
    {
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'type'=>'notin', 'values' => array('ArtistOne', 'ArtistTwo')),
                ),
            )
        );

        $this->dispatch("/test/artist?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(2, $body['count']);
    }

    public function testBetween()
    {
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'and', 'type'=>'between', 'from' => '2012-12-15', 'to' => '2013-01-01', 'format' => 'Y-m-d'),
                ),
            )
        );

        $this->dispatch("/test/artist?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);

        $this->assertEquals(1, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'or', 'type'=>'between', 'from' => '2010-12-15', 'to' => '2013-01-01', 'format' => 'Y-m-d'),
                ),
            )
        );

        $this->dispatch("/test/artist?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(2, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'type'=>'between', 'from' => '2010-12-15', 'to' => '2013-01-01', 'format' => 'Y-m-d'),
                ),
            )
        );

        $this->dispatch("/test/artist?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(2, $body['count']);
    }

    public function testLike()
    {
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'type'=>'like', 'value' => 'Artist%'),
                ),
            )
        );

        $this->dispatch("/test/artist?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(4, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'type'=>'like', 'value' => '%Two'),
                ),
            )
        );

        $this->dispatch("/test/artist?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(1, $body['count']);
    }

}
