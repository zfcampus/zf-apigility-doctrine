<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

// Because of the code-generating of Apigility this script
// is used to setup the tests.  Use ~/test/bin/reset-tests
// to reset the output of this test if the unit tests
// fail the application.

namespace ZFTest\Apigility\Doctrine\Server\ORM\CRUD;

use Doctrine\ORM\Tools\SchemaTool;
use Zend\Http\Request;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;
use ZFTest\Apigility\Doctrine\TestCase;
use ZFTestApigilityDb\Entity\Album as AlbumEntity;
use ZFTestApigilityDb\Entity\Artist as ArtistEntity;
use ZF\Apigility\Doctrine\Server\Event\DoctrineResourceEvent;

class CRUDTest extends TestCase
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
        $tool->createSchema($em->getMetadataFactory()->getAllMetadata());
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

    /**
     * @param $expectedEvents
     */
    protected function validateTriggeredEventsContains($expectedEvents)
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $eventCatcher = $serviceManager->get('ZFTestApigilityGeneral\Listener\EventCatcher');

        foreach ($expectedEvents as $event) {
            $this->assertTrue(
                in_array($event, $eventCatcher->getCaughtEvents()),
                sprintf(
                    'Did not identify event "%s" in caught events',
                    $event
                )
            );
        }
    }

    public function testCreate()
    {
        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->getRequest()->setContent('{"name": "ArtistOne","createdAt": "2011-12-18 13:17:17"}');
        $this->dispatch('/test/rest/artist');
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals('ArtistOne', $body['name']);
        $this->assertEquals(201, $this->getResponseStatusCode());
        $this->validateTriggeredEvents([
            DoctrineResourceEvent::EVENT_CREATE_PRE,
            DoctrineResourceEvent::EVENT_CREATE_POST,
        ]);

        // Test create() with listener that returns ApiProblem
        $this->reset();
        $this->setUp();

        $sharedEvents = $this->getApplication()->getEventManager()->getSharedManager();
        $sharedEvents->attach(
            'ZF\Apigility\Doctrine\DoctrineResource',
            DoctrineResourceEvent::EVENT_CREATE_PRE,
            function (DoctrineResourceEvent $e) {
                $e->stopPropagation();
                return new ApiProblem(400, 'ZFTestCreateFailure');
            }
        );

        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->getRequest()->setContent('{"name": "ArtistEleven","createdAt": "2011-12-18 13:17:17"}');
        $this->dispatch('/test/rest/artist');
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertInstanceOf('ZF\ApiProblem\ApiProblemResponse', $this->getResponse());
        $this->assertEquals('ZFTestCreateFailure', $body['detail']);
        $this->assertEquals(400, $this->getResponseStatusCode());
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

        $album = new AlbumEntity();
        $album->setName('AlbumTwo');
        $album->setArtist($artist);
        $album->setCreatedAt(new \DateTime());
        $em->persist($album);
        $em->flush();

        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_GET);
        $this->getRequest()->setContent(null);
        $this->dispatch('/test/rest/artist/' . $artist->getId());
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(200, $this->getResponseStatusCode());
        $this->assertEquals('ArtistTwo', $body['name']);
        $this->validateTriggeredEvents([
            DoctrineResourceEvent::EVENT_FETCH_PRE,
            DoctrineResourceEvent::EVENT_FETCH_POST
        ]);

        // Test fetch() of resource with non-primary key identifier
        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_GET);
        $this->getRequest()->setContent(null);
        $this->dispatch('/test/rest/artist-by-name/' . $artist->getName());
        $body = json_decode($this->getResponse()->getBody(), true);

        $this->assertEquals(200, $this->getResponseStatusCode());
        $this->assertEquals('ArtistTwo', $body['name']);

        // Test fetch() with relation
        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_GET);
        $this->getRequest()->setContent(null);
        $this->dispatch('/test/rest/artist/' . $artist->getId() . '/album/' . $album->getId());
        $body = json_decode($this->getResponse()->getBody(), true);

        $this->assertEquals(200, $this->getResponseStatusCode());
        $this->assertEquals('AlbumTwo', $body['name']);

        // Test fetch() with listener that returns ApiProblem
        $this->reset();
        $this->setUp();

        $sharedEvents = $this->getApplication()->getEventManager()->getSharedManager();
        $sharedEvents->attach(
            'ZF\Apigility\Doctrine\DoctrineResource',
            DoctrineResourceEvent::EVENT_FETCH_PRE,
            function (DoctrineResourceEvent $e) {
                $e->stopPropagation();
                return new ApiProblem(400, 'ZFTestFetchFailure');
            }
        );

        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');
        $this->dispatch('/test/rest/artist/' . $artist->getId());
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertInstanceOf('ZF\ApiProblem\ApiProblemResponse', $this->getResponse());
        $this->assertEquals(400, $this->getResponseStatusCode());
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

        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_GET);
        $this->getRequest()->setContent(null);
        $this->dispatch('/test/rest/artist');
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals(200, $this->getResponseStatusCode());
        $this->assertEquals(2, count($body['_embedded']['artist']));
        $this->validateTriggeredEvents([
            DoctrineResourceEvent::EVENT_FETCH_ALL_PRE,
            DoctrineResourceEvent::EVENT_FETCH_ALL_POST,
        ]);

        // Test fetchAll() with listener that returns ApiProblem
        $this->reset();
        $this->setUp();

        $sharedEvents = $this->getApplication()->getEventManager()->getSharedManager();
        $sharedEvents->attach(
            'ZF\Apigility\Doctrine\DoctrineResource',
            DoctrineResourceEvent::EVENT_FETCH_ALL_PRE,
            function (DoctrineResourceEvent $e) {
                $e->stopPropagation();
                return new ApiProblem(400, 'ZFTestFetchAllFailure');
            }
        );

        $this->getRequest()->setContent(null);
        $this->getRequest()->getHeaders()->addHeaderLine('Accept', 'application/json');
        $this->dispatch('/test/rest/artist?orderBy%5Bname%5D=ASC');
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertInstanceOf('ZF\ApiProblem\ApiProblemResponse', $this->getResponse());
        $this->assertEquals('ZFTestFetchAllFailure', $body['detail']);
        $this->assertEquals(400, $this->getResponseStatusCode());
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

        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_PATCH);
        $this->getRequest()->setContent('{"name":"ArtistOnePatchEdit"}');
        $this->dispatch('/test/rest/artist/' . $artist->getId());
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals('ArtistOnePatchEdit', $body['name']);

        $foundEntity = $em->getRepository('ZFTestApigilityDb\Entity\Artist')->find($artist->getId());
        $this->assertEquals('ArtistOnePatchEdit', $foundEntity->getName());
        $this->validateTriggeredEvents([
            DoctrineResourceEvent::EVENT_PATCH_PRE,
            DoctrineResourceEvent::EVENT_PATCH_POST,
        ]);

        // Test patch() with listener that returns ApiProblem
        $this->reset();
        $this->setUp();

        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');

        $artist = new ArtistEntity();
        $artist->setName('ArtistTen');
        $artist->setCreatedAt(new \Datetime());
        $em->persist($artist);
        $em->flush();

        $sharedEvents = $this->getApplication()->getEventManager()->getSharedManager();
        $sharedEvents->attach(
            'ZF\Apigility\Doctrine\DoctrineResource',
            DoctrineResourceEvent::EVENT_PATCH_PRE,
            function (DoctrineResourceEvent $e) {
                $e->stopPropagation();
                return new ApiProblem(400, 'ZFTestPatchFailure');
            }
        );

        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_PATCH);
        $this->getRequest()->setContent('{"name":"ArtistTenPatchEdit"}');
        $this->dispatch('/test/rest/artist/' . $artist->getId());
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertInstanceOf('ZF\ApiProblem\ApiProblemResponse', $this->getResponse());
        $this->assertEquals('ZFTestPatchFailure', $body['detail']);
        $this->assertEquals(400, $this->getResponseStatusCode());
    }

    public function testPatchList()
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');
        $patchList = [];

        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->getRequest()->setContent('{"name": "ArtistOne","createdAt": "2011-12-18 13:17:17"}');
        $this->dispatch('/test/rest/artist');
        $body = json_decode($this->getResponse()->getBody(), true);

        $patchList[] = [
            'id' => $body['id'],
            'name' => 'oneNewName',
        ];

        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->getRequest()->setContent('{"name": "ArtistTwo","createdAt": "2011-12-18 13:17:17"}');
        $this->dispatch('/test/rest/artist');
        $body = json_decode($this->getResponse()->getBody(), true);

        $patchList[] = [
            'id' => $body['id'],
            'name' => 'twoNewName',
        ];

        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->getRequest()->setContent('{"name": "ArtistThree","createdAt": "2011-12-18 13:17:17"}');
        $this->dispatch('/test/rest/artist');
        $body = json_decode($this->getResponse()->getBody(), true);

        $patchList[] = [
            'id' => $body['id'],
            'name' => 'threeNewName',
        ];

        $em->clear();

        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_PATCH);
        $this->getRequest()->setContent(json_encode($patchList));
        $this->dispatch('/test/rest/artist');

        $body = json_decode($this->getResponse()->getBody(), true);

        $this->assertEquals('oneNewName', $body['_embedded']['artist'][0]['name']);
        $this->assertEquals('twoNewName', $body['_embedded']['artist'][1]['name']);
        $this->assertEquals('threeNewName', $body['_embedded']['artist'][2]['name']);

        $this->validateTriggeredEventsContains([
            DoctrineResourceEvent::EVENT_PATCH_LIST_PRE,
            DoctrineResourceEvent::EVENT_PATCH_LIST_POST,
        ]);

        // Test patch() with listener that returns ApiProblem
        $this->reset();
        $this->setUp();

        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');

        $artist = new ArtistEntity();
        $artist->setName('ArtistTen');
        $artist->setCreatedAt(new \Datetime());
        $em->persist($artist);
        $em->flush();

        $sharedEvents = $this->getApplication()->getEventManager()->getSharedManager();
        $sharedEvents->attach(
            'ZF\Apigility\Doctrine\DoctrineResource',
            DoctrineResourceEvent::EVENT_PATCH_LIST_POST,
            function (DoctrineResourceEvent $e) {
                $e->stopPropagation();
                return new ApiProblem(400, 'ZFTestPatchFailure');
            }
        );

        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_PATCH);
        $this->getRequest()->setContent('[{"id": "' . $artist->getId() . '", "name":"ArtistTenPatchEdit"}]');
        $this->dispatch('/test/rest/artist');
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertInstanceOf('ZF\ApiProblem\ApiProblemResponse', $this->getResponse());
        $this->assertEquals('ZFTestPatchFailure', $body['detail']);
        $this->assertEquals(400, $this->getResponseStatusCode());
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

        $this->getRequest()->getHeaders()->addHeaders(
            [
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
            ]
        );
        $this->getRequest()->setMethod(Request::METHOD_PUT);
        $this->getRequest()->setContent('{"name": "ArtistSevenPutEdit","createdAt": "2012-12-18 13:17:17"}');
        $this->dispatch('/test/rest/artist/' . $artist->getId());
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals('ArtistSevenPutEdit', $body['name']);

        $foundEntity = $em->getRepository('ZFTestApigilityDb\Entity\Artist')->find($artist->getId());
        $this->assertEquals('ArtistSevenPutEdit', $foundEntity->getName());
        $this->validateTriggeredEvents([
            DoctrineResourceEvent::EVENT_UPDATE_PRE,
            DoctrineResourceEvent::EVENT_UPDATE_POST,
        ]);

        // Test put() with listener that returns ApiProblem
        $this->reset();
        $this->setUp();

        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');

        $artist = new ArtistEntity();
        $artist->setName('ArtistNine');
        $artist->setCreatedAt(new \Datetime());
        $em->persist($artist);
        $em->flush();

        $sharedEvents = $this->getApplication()->getEventManager()->getSharedManager();
        $sharedEvents->attach(
            'ZF\Apigility\Doctrine\DoctrineResource',
            DoctrineResourceEvent::EVENT_UPDATE_PRE,
            function (DoctrineResourceEvent $e) {
                $e->stopPropagation();
                return new ApiProblem(400, 'ZFTestPutFailure');
            }
        );

        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_PUT);
        $this->getRequest()->setContent('{"name": "ArtistNinePutEdit","createdAt": "2012-12-18 13:17:17"}');
        $this->dispatch('/test/rest/artist/' . $artist->getId());
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertInstanceOf('ZF\ApiProblem\ApiProblemResponse', $this->getResponse());
        $this->assertEquals('ZFTestPutFailure', $body['detail']);
        $this->assertEquals(400, $this->getResponseStatusCode());
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

        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_DELETE);
        $this->dispatch('/test/rest/artist/' . $artist->getId());
        $this->assertEquals(204, $this->getResponseStatusCode());

        $this->assertEmpty($em->getRepository('ZFTestApigilityDb\Entity\Artist')->find($id));
        $this->validateTriggeredEvents([
            DoctrineResourceEvent::EVENT_DELETE_PRE,
            DoctrineResourceEvent::EVENT_DELETE_POST,
        ]);

        // Test delete() with listener that returns ApiProblem
        $this->reset();
        $this->setUp();

        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');

        $artist = new ArtistEntity();
        $artist->setName('ArtistEight');
        $artist->setCreatedAt(new \Datetime());
        $em->persist($artist);
        $em->flush();

        $sharedEvents = $this->getApplication()->getEventManager()->getSharedManager();
        $sharedEvents->attach(
            'ZF\Apigility\Doctrine\DoctrineResource',
            DoctrineResourceEvent::EVENT_DELETE_PRE,
            function (DoctrineResourceEvent $e) {
                $e->stopPropagation();
                return new ApiProblem(400, 'ZFTestDeleteFailure');
            }
        );

        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_DELETE);
        $this->dispatch('/test/rest/artist/' . $artist->getId());
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertInstanceOf('ZF\ApiProblem\ApiProblemResponse', $this->getResponse());
        $this->assertEquals('ZFTestDeleteFailure', $body['detail']);
        $this->assertEquals(400, $this->getResponseStatusCode());

        // Test DELETE: entity not found

        $this->reset();
        $this->setUp();

        $id = -1;

        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_DELETE);
        $this->dispatch('/test/rest/artist/' . $artist->getId());
        $body = ($this->getResponse()->getBody());
        $this->assertEquals(404, $this->getResponseStatusCode());
        $this->validateTriggeredEvents([]);
    }

    public function testDeleteList()
    {
        $serviceManager = $this->getApplication()->getServiceManager();
        $em = $serviceManager->get('doctrine.entitymanager.orm_default');
        $deleteList = [];

        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->getRequest()->setContent('{"name": "ArtistOne","createdAt": "2011-12-18 13:17:17"}');
        $this->dispatch('/test/rest/artist');
        $body = json_decode($this->getResponse()->getBody(), true);

        $deleteList[] = [
            'id' => $body['id'],
        ];

        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->getRequest()->setContent('{"name": "ArtistTwo","createdAt": "2011-12-18 13:17:17"}');
        $this->dispatch('/test/rest/artist');
        $body = json_decode($this->getResponse()->getBody(), true);

        $deleteList[] = [
            'id' => $body['id'],
        ];

        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->getRequest()->setContent('{"name": "ArtistThree","createdAt": "2011-12-18 13:17:17"}');
        $this->dispatch('/test/rest/artist');
        $body = json_decode($this->getResponse()->getBody(), true);

        $deleteList[] = [
            'id' => $body['id'],
        ];

        $em->clear();

        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_DELETE);
        $this->getRequest()->setContent(json_encode($deleteList));
        $this->dispatch('/test/rest/artist');
        $this->assertEquals(204, $this->getResponseStatusCode());

        $this->validateTriggeredEventsContains([
            DoctrineResourceEvent::EVENT_DELETE_LIST_PRE,
            DoctrineResourceEvent::EVENT_DELETE_LIST_POST,
        ]);
    }

    public function testRpcController()
    {
        $this->getRequest()->getHeaders()->addHeaders([
            'Accept' => 'application/json',
            'Content-type' => 'application/json',
        ]);
        $this->getRequest()->setMethod(Request::METHOD_POST);

        $this->getRequest()->setContent('{"name": "ArtistOne","createdAt": "2011-12-18 13:17:17"}');
        $this->dispatch('/test/rest/artist');
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals('ArtistOne', $body['name']);
        $this->assertEquals(201, $this->getResponseStatusCode());

        $artistId = $body['id'];

        $this->getRequest()->setContent(
            '{"name": "AlbumOne","createdAt": "2011-12-18 13:17:17","artist": "' . $artistId . '"}'
        );
        $this->dispatch('/test/rest/album');
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals('AlbumOne', $body['name']);
        $this->assertEquals(201, $this->getResponseStatusCode());

        $this->getRequest()->setContent(
            '{"name": "AlbumTwo","createdAt": "2011-12-18 13:17:17","artist": "' . $artistId . '"}'
        );
        $this->dispatch('/test/rest/album');
        $body = json_decode($this->getResponse()->getBody(), true);
        $this->assertEquals('AlbumTwo', $body['name']);
        $this->assertEquals(201, $this->getResponseStatusCode());

        $albumId = $body['id'];
    }
}
