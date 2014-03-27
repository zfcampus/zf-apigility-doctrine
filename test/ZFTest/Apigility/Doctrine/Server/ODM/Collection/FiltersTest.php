<?php

namespace ZFTest\Apigility\Doctrine\Admin\Server\ODM\Collection;

use Zend\Http\Request;

class FiltersTest extends \Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase
{
    public function setUp()
    {
        $this->setApplicationConfig(
            include __DIR__ . '/../../../../../../config/ODM/application.config.php'
        );
        parent::setUp();

        $config = $this->getApplication()->getConfig()['doctrine']['connection']['odm_default'];

        $connection = new \MongoClient('mongodb://' . $config['server'] . ':' . $config['port']);
        $db = $connection->{$config['dbname']};
        $collection = $db->meta;
        $collection->remove();

        $serviceManager = $this->getApplication()->getServiceManager();

        $this->getRequest()->getHeaders()->addHeaders(array(
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ));

        $this->getRequest()->setMethod(Request::METHOD_POST);

        $this->getRequest()->setContent('{"name": "MetaOne","createdAt": "2011-12-18 13:17:17"}');
        $this->dispatch('/test/meta');

        $this->getRequest()->setContent('{"name": "MetaTwo","createdAt": "2014-12-18 13:17:17"}');
        $this->dispatch('/test/meta');

        $this->getRequest()->setContent('{"name": "MetaThree","createdAt": "2012-12-18 13:17:17"}');
        $this->dispatch('/test/meta');

        $this->getRequest()->setContent('{"name": "MetaFour","createdAt": "2013-12-18 13:17:17"}');
        $this->dispatch('/test/meta');

        $this->getRequest()->setMethod(Request::METHOD_GET);
        $this->getRequest()->setContent(null);
    }

    public function testEquals()
    {
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'type'=>'eq', 'value' => 'MetaOne'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(1, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'and', 'type'=>'eq', 'value' => '2014-12-18 13:17:17', 'format' => 'Y-m-d H:i:s'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(1, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'or', 'type'=>'eq', 'value' => '2014-12-18 13:17:17', 'format' => 'Y-m-d H:i:s'),
                    array('field' =>'createdAt', 'where' => 'or', 'type'=>'eq', 'value' => '2012-12-18 13:17:17', 'format' => 'Y-m-d H:i:s'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(2, $body['count']);
    }

    public function testNotEquals()
    {
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'type'=>'neq', 'value' => 'MetaOne'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(3, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'or', 'type'=>'neq', 'value' => '2014-12-18 13:17:17', 'format' => 'Y-m-d H:i:s'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(3, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'and', 'type'=>'neq', 'value' => '2014-12-18 13:17:17', 'format' => 'Y-m-d H:i:s'),
                    array('field' =>'createdAt', 'where' => 'and', 'type'=>'neq', 'value' => '2012-12-18 13:17:17', 'format' => 'Y-m-d H:i:s'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(2, $body['count']);
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

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(3, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'and', 'type'=>'lt', 'value' => '2013-12-18 13:17:17'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(2, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'or', 'type'=>'lt', 'value' => '2013-12-18 13:17:17'),
                    array('field' =>'name', 'where' => 'or', 'type' => 'eq', 'value'=>'MetaTwo'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(3, $body['count']);
    }

    public function testLessThanOrEquals()
    {
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'type'=>'lte', 'value' => '2011-12-20', 'format' => 'Y-m-d'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(1, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'type'=>'lte', 'value' => '2011-12-18 13:17:16'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(0, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'and', 'type'=>'lte', 'value' => '2013-12-18 13:17:17'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(3, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'or', 'type'=>'lte', 'value' => '2013-12-18 13:17:17'),
                    array('field' =>'name', 'where' => 'or', 'type' => 'eq', 'value'=>'MetaTwo'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(4, $body['count']);
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

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(1, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'or', 'type'=>'gt', 'value' => '2013-12-18 13:17:17', 'format' => 'Y-m-d H:i:s'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(1, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'and', 'type'=>'gt', 'value' => '2013-12-18 13:17:17', 'format' => 'Y-m-d H:i:s'),
                    array('field' =>'createdAt', 'where' => 'and', 'type'=>'gt', 'value' => '2012-12-18 13:17:17', 'format' => 'Y-m-d H:i:s'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
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

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(1, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'type'=>'gte', 'value' => '2014-12-18 13:17:18'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(0, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'and', 'type'=>'gte', 'value' => '2013-12-18 13:17:17', 'format' => 'Y-m-d H:i:s'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(2, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'or', 'type'=>'gte', 'value' => '2013-12-18 13:17:17', 'format' => 'Y-m-d H:i:s'),
                    array('field' =>'createdAt', 'where' => 'or', 'type'=>'gte', 'value' => '2012-12-18 13:17:17', 'format' => 'Y-m-d H:i:s'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(3, $body['count']);
    }

    public function testIsNull()
    {
        $this->getRequest()->getHeaders()->addHeaders(array(
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ));

        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->getRequest()->setContent('{"name": "MetaFive"}');
        $this->dispatch('/test/meta');

        $this->getRequest()->setMethod(Request::METHOD_GET);
        $this->getRequest()->setContent(null);
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'type'=>'isnull'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(1, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'and', 'type'=>'isnull'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(1, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'or', 'type' => 'isnull'),
                    array('field' =>'name', 'where' => 'or', 'type' => 'eq', 'value'=>'MetaOne'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(2, $body['count']);
    }

    public function testIsNotNull()
    {
        $this->getRequest()->getHeaders()->addHeaders(array(
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ));

        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->getRequest()->setContent('{"name": "MetaFive"}');
        $this->dispatch('/test/meta');

        $this->getRequest()->setMethod(Request::METHOD_GET);
        $this->getRequest()->setContent(null);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'type'=>'isnotnull'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(4, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'and', 'type'=>'isnotnull'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(4, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'or', 'type' => 'isnotnull'),
                    array('field' =>'name', 'where' => 'or', 'type' => 'eq', 'value'=>'MetaFive'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(5, $body['count']);
    }

    public function testIn()
    {
        // Date handling in IN and NOTIN doesn't seem to work at all, so just test with strings
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'type'=>'in', 'values' => array('MetaOne', 'MetaTwo')),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(2, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'type'=>'in', 'values' => array('MetaOne'), 'where' => 'and'),
                ),
            )
        );
        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(1, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'type'=>'in', 'values' => array('MetaOne'), 'where' => 'or'),
                ),
            )
        );
        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);

        // count is 2 because null is not counted in a notin
        $this->assertEquals(1, $body['count']);
    }

    public function testNotIn()
    {
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'type'=>'notin', 'values' => array('MetaOne', 'MetaTwo')),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(2, $body['count']);

        // Test date field
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'where' => 'and', 'type'=>'notin', 'values' => array('MetaOne')),
                ),
            )
        );
        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(3, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'where' => 'or', 'type'=>'notin', 'values' => array('MetaTwo')),
                ),
            )
        );
        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);

        // count is 2 because null is not counted in a notin
        $this->assertEquals(3, $body['count']);
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

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);

        $this->assertEquals(1, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'where' => 'or', 'type'=>'between', 'from' => '2010-12-15', 'to' => '2013-01-01', 'format' => 'Y-m-d'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(2, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'createdAt', 'type'=>'between', 'from' => '2010-12-15', 'to' => '2013-01-01', 'format' => 'Y-m-d'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(2, $body['count']);
    }

    public function testLike()
    {
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'type'=>'like', 'value' => 'Meta%'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(4, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'type'=>'like', 'value' => '%Two'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(1, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'where' => 'and', 'type'=>'like', 'value' => '%eta%'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(4, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'where' => 'or', 'type' => 'like', 'value' => 'MetaT%'),
                    array('field' =>'name', 'where' => 'or', 'type' => 'like', 'value'=>'MetaF%'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(3, $body['count']);
    }

    public function testRegex()
    {
        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'type'=>'regex', 'value' => '/.*T.*$/'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(2, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'where' => 'or', 'type'=>'regex', 'value' => '/.*T.*$/'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(2, $body['count']);

        $queryString = http_build_query(
            array(
                'query' => array(
                    array('field' =>'name', 'where' => 'and', 'type'=>'regex', 'value' => '/.*T.*$/'),
                ),
            )
        );

        $this->dispatch("/test/meta?$queryString");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(2, $body['count']);
    }
}
