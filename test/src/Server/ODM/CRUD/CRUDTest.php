<?php
// Because of the code-generating of Apigility this script
// is used to setup the tests.  Use ~/test/bin/reset-tests
// to reset the output of this test if the unit tests
// fail the application.

namespace ZFTest\Apigility\Doctrine\Server\ODM\CRUD;

use General\Listener\EventCatcher;
use Zend\Http\Request;
use DbMongo\Document\Meta as MetaEntity;
use ZF\Apigility\Doctrine\Server\Event\DoctrineResourceEvent;

class CRUDTest extends \Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase
{
    public function setUp()
    {
        $this->setApplicationConfig(
            include __DIR__ . '/../../../../config/ODM/application.config.php'
        );
        parent::setUp();
    }

    protected function clearData()
    {
        $config = $this->getApplication()->getConfig()['doctrine']['connection']['odm_default'];

        $connection = new \MongoClient('mongodb://' . $config['server'] . ':' . $config['port']);
        $db = $connection->{$config['dbname']};
        $collection = $db->meta;
        $collection->remove();
    }

    /**
     * @param $expectedEvents
     */
    protected function validateTriggeredEvents($expectedEvents)
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $eventCatcher = $serviceManager->get('General\Listener\EventCatcher');

        $this->assertEquals($expectedEvents, $eventCatcher->getCaughtEvents());
    }

    public function testCreate()
    {
        $this->getRequest()->getHeaders()->addHeaders(array(
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ));
        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->getRequest()->setContent('{"name": "ArtistOne","createdAt": "2011-12-18 13:17:17"}');
        $this->dispatch('/test/meta');
        $body = json_decode($this->getResponse()->getBody(), true);

        $this->assertEquals('ArtistOne', $body['name']);
        $this->assertEquals(201, $this->getResponseStatusCode());
        $this->validateTriggeredEvents([DoctrineResourceEvent::EVENT_CREATE_PRE, DoctrineResourceEvent::EVENT_CREATE_POST]);
    }

    public function testFetch()
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $dm = $serviceManager->get('doctrine.documentmanager.odm_default');
        $this->clearData();

        $meta = new MetaEntity();
        $meta->setName('ArtistTwo');
        $meta->setCreatedAt(new \Datetime());
        $dm->persist($meta);
        $dm->flush();

        $this->getRequest()->getHeaders()->addHeaders(array(
            'Accept' => 'application/json',
        ));
        $this->getRequest()->setMethod(Request::METHOD_GET);
        $this->getRequest()->setContent(null);
        $this->dispatch('/test/meta/' . $meta->getId());
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(200, $this->getResponseStatusCode());
        $this->assertEquals('ArtistTwo', $body['name']);
        $this->validateTriggeredEvents([DoctrineResourceEvent::EVENT_FETCH_POST]);
    }

    public function testFetchAll()
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $dm = $serviceManager->get('doctrine.documentmanager.odm_default');
        $this->clearData();

        $meta = new MetaEntity();
        $meta->setName('ArtistThree');
        $meta->setCreatedAt(new \Datetime());
        $dm->persist($meta);
        $meta = new MetaEntity();
        $meta->setName('ArtistFour');
        $meta->setCreatedAt(new \Datetime());
        $dm->persist($meta);
        $dm->flush();

        $this->getRequest()->getHeaders()->addHeaders(array(
            'Accept' => 'application/json',
        ));
        $this->getRequest()->setMethod(Request::METHOD_GET);
        $this->getRequest()->setContent(null);
        $this->dispatch('/test/meta?orderBy%5Bname%5D=ASC');
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(200, $this->getResponseStatusCode());
        $this->assertEquals(2, sizeof($body['_embedded']['meta']));
        $this->validateTriggeredEvents([DoctrineResourceEvent::EVENT_FETCH_ALL_POST]);
    }
/*
    public function testPatch()
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');

        $artist = new ArtistEntity();
        $artist->setName('ArtistSix');
        $artist->setCreatedAt(new \Datetime());
        $em->persist($artist);
        $em->flush();

        $this->getRequest()->getHeaders()->addHeaders(array(
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ));
        $this->getRequest()->setMethod(Request::METHOD_PATCH);
        $this->getRequest()->setContent('{"name":"ArtistOnePatchEdit"}');
        $this->dispatch('/test/artist/' . $artist->getId());
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals('ArtistOnePatchEdit', $body['name']);

        $foundEntity = $em->getRepository('Db\Entity\Artist')->find($artist->getId());
        $this->assertEquals('ArtistOnePatchEdit', $foundEntity->getName());
        $this->validateTriggeredEvents([DoctrineResourceEvent::EVENT_PATCH_PRE, DoctrineResourceEvent::EVENT_PATCH_POST]);
    }

    public function testPut()
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');

        $artist = new ArtistEntity();
        $artist->setName('ArtistSeven');
        $artist->setCreatedAt(new \Datetime());
        $em->persist($artist);
        $em->flush();

        $this->getRequest()->getHeaders()->addHeaders(array(
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ));
        $this->getRequest()->setMethod(Request::METHOD_PUT);
        $this->getRequest()->setContent('{"name": "ArtistSevenPutEdit","createdAt": "2012-12-18 13:17:17"}');
        $this->dispatch('/test/artist/' . $artist->getId());
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals('ArtistSevenPutEdit', $body['name']);

        $foundEntity = $em->getRepository('Db\Entity\Artist')->find($artist->getId());
        $this->assertEquals('ArtistSevenPutEdit', $foundEntity->getName());
        $this->validateTriggeredEvents([DoctrineResourceEvent::EVENT_UPDATE_PRE, DoctrineResourceEvent::EVENT_UPDATE_POST]);
    }

    public function testDelete()
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');

        $artist = new ArtistEntity();
        $artist->setName('ArtistFive');
        $artist->setCreatedAt(new \Datetime());
        $em->persist($artist);
        $em->flush();

        $id = $artist->getId();

        $this->getRequest()->getHeaders()->addHeaders(array(
            'Accept' => 'application/json',
        ));
        $this->getRequest()->setMethod(Request::METHOD_DELETE);
        $this->dispatch('/test/artist/' . $artist->getId());
        $this->assertEquals(204, $this->getResponseStatusCode());

        $this->assertEmpty($em->getRepository('Db\Entity\Artist')->find($id));
        $this->validateTriggeredEvents([DoctrineResourceEvent::EVENT_DELETE_PRE, DoctrineResourceEvent::EVENT_DELETE_POST]);

        // Test DELETE: entity not found

        $this->reset();
        $this->setUp();

        $id = -1;

        $this->getRequest()->getHeaders()->addHeaders(array(
            'Accept' => 'application/json',
        ));
        $this->getRequest()->setMethod(Request::METHOD_DELETE);
        $this->dispatch('/test/artist/' . $artist->getId());
        $this->assertEquals(404, $this->getResponseStatusCode());

    }

*/

}
