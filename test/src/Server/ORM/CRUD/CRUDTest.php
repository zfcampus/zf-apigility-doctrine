<?php
// Because of the code-generating of Apigility this script
// is used to setup the tests.  Use ~/test/bin/reset-tests
// to reset the output of this test if the unit tests
// fail the application.

namespace ZFTest\Apigility\Doctrine\Server\ORM\CRUD;

use Doctrine\ORM\Tools\SchemaTool;
use Zend\Http\Request;
use ZFTestApigilityDb\Entity\Artist as ArtistEntity;
use ZF\Apigility\Doctrine\Server\Event\DoctrineResourceEvent;

class CRUDTest extends \Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase
{
    public function setUp()
    {
        $this->setApplicationConfig(
            include __DIR__ . '/../../../../config/ORM/application.config.php'
        );
        parent::setUp();

        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');

        $tool = new SchemaTool($em);
        $res = $tool->createSchema($em->getMetadataFactory()->getAllMetadata());
    }

    /**
     * @param $expectedEvents
     */
    protected function validateTriggeredEvents($expectedEvents)
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $eventCatcher = $serviceManager->get('ZFTestApigilityGeneral\Listener\EventCatcher');

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
        $this->dispatch('/test/artist');
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals('ArtistOne', $body['name']);
        $this->assertEquals(201, $this->getResponseStatusCode());
        $this->validateTriggeredEvents(array(
            DoctrineResourceEvent::EVENT_CREATE_PRE,
            DoctrineResourceEvent::EVENT_CREATE_POST,
        ));
    }

    public function testFetch()
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');

        $artist = new ArtistEntity();
        $artist->setName('ArtistTwo');
        $artist->setCreatedAt(new \Datetime());
        $em->persist($artist);
        $em->flush();

        $this->getRequest()->getHeaders()->addHeaders(array(
            'Accept' => 'application/json',
        ));
        $this->getRequest()->setMethod(Request::METHOD_GET);
        $this->getRequest()->setContent(null);
        $this->dispatch('/test/artist/' . $artist->getId());
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(200, $this->getResponseStatusCode());
        $this->assertEquals('ArtistTwo', $body['name']);
        $this->validateTriggeredEvents(array(DoctrineResourceEvent::EVENT_FETCH_POST));
    }

    public function testFetchAll()
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');

        $artist = new ArtistEntity();
        $artist->setName('ArtistThree');
        $artist->setCreatedAt(new \Datetime());
        $em->persist($artist);
        $artist = new ArtistEntity();
        $artist->setName('ArtistFour');
        $artist->setCreatedAt(new \Datetime());
        $em->persist($artist);
        $em->flush();

        $this->getRequest()->getHeaders()->addHeaders(array(
            'Accept' => 'application/json',
        ));
        $this->getRequest()->setMethod(Request::METHOD_GET);
        $this->getRequest()->setContent(null);
        $this->dispatch('/test/artist?orderBy%5Bname%5D=ASC');
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(200, $this->getResponseStatusCode());
        $this->assertEquals(2, count($body['_embedded']['artist']));
        $this->validateTriggeredEvents(array(
            DoctrineResourceEvent::EVENT_FETCH_ALL_PRE,
            DoctrineResourceEvent::EVENT_FETCH_ALL_POST,
        ));
    }

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

        $foundEntity = $em->getRepository('ZFTestApigilityDb\Entity\Artist')->find($artist->getId());
        $this->assertEquals('ArtistOnePatchEdit', $foundEntity->getName());
        $this->validateTriggeredEvents(array(
            DoctrineResourceEvent::EVENT_PATCH_PRE,
            DoctrineResourceEvent::EVENT_PATCH_POST,
        ));
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

        $foundEntity = $em->getRepository('ZFTestApigilityDb\Entity\Artist')->find($artist->getId());
        $this->assertEquals('ArtistSevenPutEdit', $foundEntity->getName());
        $this->validateTriggeredEvents(array(
            DoctrineResourceEvent::EVENT_UPDATE_PRE,
            DoctrineResourceEvent::EVENT_UPDATE_POST,
        ));
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

        $this->assertEmpty($em->getRepository('ZFTestApigilityDb\Entity\Artist')->find($id));
        $this->validateTriggeredEvents(array(
            DoctrineResourceEvent::EVENT_DELETE_PRE,
            DoctrineResourceEvent::EVENT_DELETE_POST,
        ));

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
        $this->validateTriggeredEvents(array());
    }

    public function testRpcController()
    {
        $this->getRequest()->getHeaders()->addHeaders(array(
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ));
        $this->getRequest()->setMethod(Request::METHOD_POST);

        $this->getRequest()->setContent('{"name": "ArtistOne","createdAt": "2011-12-18 13:17:17"}');
        $this->dispatch('/test/artist');
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals('ArtistOne', $body['name']);
        $this->assertEquals(201, $this->getResponseStatusCode());

        $artistId = $body['id'];

        $this->getRequest()->setContent(
            '{"name": "AlbumOne","createdAt": "2011-12-18 13:17:17","artist": "' . $artistId . '"}'
        );
        $this->dispatch('/test/album');
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals('AlbumOne', $body['name']);
        $this->assertEquals(201, $this->getResponseStatusCode());

        $this->getRequest()->setContent(
            '{"name": "AlbumTwo","createdAt": "2011-12-18 13:17:17","artist": "' . $artistId . '"}'
        );
        $this->dispatch('/test/album');
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals('AlbumTwo', $body['name']);
        $this->assertEquals(201, $this->getResponseStatusCode());

        $albumId = $body['id'];

        $this->getRequest()->setMethod(Request::METHOD_GET);
        $this->getRequest()->setContent(null);
        $this->dispatch("/test/artist/$artistId/album");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(2, $body['count']);

        $this->dispatch("/test/artist/$artistId/album/$albumId");
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals('AlbumTwo', $body['name']);
    }
}
